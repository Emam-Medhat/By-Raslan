<?php

namespace App\Modules\Sales\Services;

use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\ItemBarcode;
use App\Modules\Catalog\Models\Price;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleLine;
use App\Modules\Sales\Models\Shift;
use Illuminate\Support\Facades\DB;

/**
 * Point-of-sale core. Prices always come from the DB, stock is deducted via
 * the append-only ledger, and sales are idempotent per cashier.
 */
class PosService
{
    public function openShift(string $location, float $cash, string $userId): Shift
    {
        if ($cash < 0 || $cash > 9_999_999_999.99 || round($cash, 2) != $cash) {
            throw new \RuntimeException('رصيد بداية الوردية غير صحيح');
        }

        $loc = \App\Modules\Organization\Models\Location::where('id', $location)
            ->where('active', true)
            ->whereIn('type', ['dark', 'shop'])
            ->first();

        if (! $loc) {
            throw new \RuntimeException('الفرع غير متاح للبيع');
        }

        $existing = Shift::where('cashier', $userId)->where('status', 'open')->first();
        if ($existing) {
            return $existing;
        }

        return Shift::create([
            'location_id' => $location,
            'cashier' => $userId,
            'open_cashier' => $userId,
            'opened_at' => now(),
            'opening_cash' => round($cash, 2),
            'status' => 'open',
        ]);
    }

    /** @return array{item_id:int,item_name:string,unit:string,box_sn:int|null,qty:float,unit_price:float} */
    public function lookup(string $location, string $code): array
    {
        $locOk = \App\Modules\Organization\Models\Location::where('id', $location)
            ->where('active', true)
            ->whereIn('type', ['dark', 'shop'])
            ->exists();
        if (! $locOk) {
            throw new \RuntimeException('الفرع غير متاح للبيع');
        }

        $code = trim($code);
        $upper = strtoupper($code);

        if (preg_match('/^R?[0-9]{1,9}(\|.*)?$/', $upper) && ! ItemBarcode::where('code', $code)->exists()) {
            $sn = (int) preg_replace('/\D+/', '', explode('|', $upper)[0]);
            $box = Box::with('item')->find($sn);

            if ($box) {
                $statusMessages = [
                    'sold' => 'اتباعت قبل كده',
                    'transit' => 'لسه في الطريق — استلمها الأول',
                    'loaded' => 'على عربية',
                    'waste' => 'متسجّلة هالك',
                    'missing' => 'متسجّلة ناقصة',
                    'donated' => 'متبرع بيها',
                ];
                if ($box->status !== 'stock') {
                    $msg = $statusMessages[$box->status] ?? 'مش متاحة للبيع';
                    throw new \RuntimeException("العلبة #{$sn} {$msg}");
                }
                if ($box->location_id !== $location) {
                    throw new \RuntimeException("العلبة #{$sn} مش في الفرع ده");
                }
                if (! $box->item || ! $box->item->active) {
                    throw new \RuntimeException('الصنف غير فعال');
                }
                if ((float) $box->weight <= 0) {
                    throw new \RuntimeException('وزن العلبة غير صحيح');
                }

                $price = $this->price($box->item_id, $location);
                if ($price === null || $price < 0) {
                    throw new \RuntimeException('مفيش سعر صالح لهذا الصنف في الفرع');
                }

                $qty = ($box->item->unit === 'قطعة' || $box->item->sold_by === 'piece') ? 1.0 : (float) $box->weight;

                return [
                    'item_id' => $box->item_id,
                    'item_name' => $box->item->name,
                    'unit' => $box->item->unit,
                    'box_sn' => $box->sn,
                    'qty' => $qty,
                    'unit_price' => $price,
                ];
            }
        }

        $barcode = ItemBarcode::with('item')->where('code', $code)->first();

        if (! $barcode || ! $barcode->item) {
            throw new \RuntimeException('الكود ده مش متسجّل');
        }

        $item = $barcode->item;
        if (! $item->active) {
            throw new \RuntimeException('الصنف غير فعال');
        }
        if ($item->arm === 'rostos' && ($item->kind ?? 'product') === 'product') {
            throw new \RuntimeException("{$item->name} بيتباع بالعلبة — امسح QR العلبة");
        }

        $price = $this->price($item->id, $location);
        if ($price === null || $price < 0) {
            throw new \RuntimeException('مفيش سعر صالح لهذا الصنف في الفرع');
        }

        return [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'unit' => $item->unit,
            'box_sn' => null,
            'qty' => 1.0,
            'unit_price' => $price,
        ];
    }

    /**
     * Live product search for the POS screen (direct-channel price).
     *
     * @return list<array{item_id:int,name:string,unit:string,price:float}>
     */
    public function search(string $location, string $query): array
    {
        $query = trim($query);

        return \App\Modules\Catalog\Models\Item::query()
            ->join('prices', 'prices.item_id', '=', 'items.id')
            ->where('prices.location_id', $location)
            ->where('prices.channel', 'direct')
            ->where('items.active', true)
            ->when($query !== '', fn ($q) => $q->where('items.name', 'like', '%'.$query.'%'))
            ->select('items.id as item_id', 'items.name', 'items.unit', 'prices.price')
            ->orderBy('items.name')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'item_id' => (int) $r->item_id,
                'name' => $r->name,
                'unit' => $r->unit,
                'price' => (float) $r->price,
            ])
            ->all();
    }

    private function price(int $itemId, string $location): ?float
    {
        $price = Price::where('item_id', $itemId)
            ->where('location_id', $location)
            ->where('channel', 'direct')
            ->value('price');

        return $price !== null ? (float) $price : null;
    }

    private function available(int $itemId, string $location): float
    {
        return (float) StockMove::where('item_id', $itemId)->where('location_id', $location)->sum('qty');
    }

    /**
     * @param  list<array{box_sn?:int,item_id?:int,qty?:float}>  $lines
     */
    public function sale(int $shiftId, array $lines, float $discount, float $cash, float $card, string $idempotencyKey, string $userId): Sale
    {
        foreach (['discount' => $discount, 'cash' => $cash, 'card' => $card] as $name => $value) {
            if (! is_finite($value) || $value < 0 || $value > 9_999_999_999.99 || round($value, 2) != $value) {
                throw new \RuntimeException('راجع قيم الخصم والمدفوع');
            }
        }

        $payload = [
            'shift' => $shiftId,
            'lines' => $lines,
            'discount' => round($discount, 2),
            'cash' => round($cash, 2),
            'card' => round($card, 2),
        ];

        return DB::transaction(function () use ($shiftId, $lines, $discount, $cash, $card, $idempotencyKey, $userId, $payload) {
            $shift = Shift::where('id', $shiftId)
                ->where('cashier', $userId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                throw new \RuntimeException('افتح وردية الأول');
            }

            $existing = Sale::where('cashier', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existingPayload = $existing->request_payload;
                if (is_string($existingPayload)) {
                    $existingPayload = json_decode($existingPayload, true);
                }
                if (json_encode($existingPayload) !== json_encode($payload)) {
                    throw new \RuntimeException('مفتاح العملية مستخدم لطلب بيع مختلف');
                }

                return $existing;
            }

            if ($lines === []) {
                throw new \RuntimeException('الفاتورة فاضية');
            }

            $actor = \App\Models\User::find($userId);
            if (! $actor || ! $actor->isActive()) {
                throw new \RuntimeException('سجّل دخول بحساب فعال');
            }

            if ($discount > 0) {
                $limit = $actor->permissionLimit('pos.discount');
                $canDiscount = $actor->hasPermission('pos.discount');
                if (! $canDiscount) {
                    throw new \RuntimeException('الخصم محتاج صلاحية');
                }
                if ($limit !== null && $discount > $limit) {
                    throw new \RuntimeException("الخصم ({$discount} ج) أكبر من حدك ({$limit} ج)");
                }
            }

            $sub = 0.0;
            $index = 0;
            $seen = [];
            $saleLines = [];
            $moves = [];

            foreach ($lines as $line) {
                $index++;

                if (isset($line['box_sn'])) {
                    $sn = (int) $line['box_sn'];

                    if (in_array($sn, $seen, true)) {
                        throw new \RuntimeException("العلبة #{$sn} متكررة في الفاتورة");
                    }
                    $seen[] = $sn;

                    $box = Box::with('item')->where('sn', $sn)->lockForUpdate()->first();

                    if (! $box || $box->status !== 'stock' || $box->location_id !== $shift->location_id) {
                        throw new \RuntimeException("العلبة #{$sn} لم تعد متاحة للبيع");
                    }
                    if (! $box->item || ! $box->item->active) {
                        throw new \RuntimeException('الصنف غير فعال');
                    }

                    $price = $this->price($box->item_id, $shift->location_id);
                    if ($price === null) {
                        throw new \RuntimeException('مفيش سعر لصنف العلبة');
                    }

                    $qty = ($box->item->unit === 'قطعة' || $box->item->sold_by === 'piece') ? 1.0 : (float) $box->weight;
                    $lineTotal = round($qty * $price, 2);

                    $saleLines[] = ['line' => $index, 'item_id' => $box->item_id, 'box_sn' => $sn, 'qty' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
                    $moves[] = ['item_id' => $box->item_id, 'location_id' => $shift->location_id, 'qty' => -abs((float) $box->weight), 'kind' => 'sale', 'box_sn' => $sn];

                    $box->update(['status' => 'sold']);
                    $sub += $lineTotal;
                } else {
                    $itemId = (int) ($line['item_id'] ?? 0);
                    $qty = (float) ($line['qty'] ?? 0);

                    if ($itemId <= 0 || $qty <= 0) {
                        throw new \RuntimeException('الصنف أو الكمية غير صحيحة');
                    }

                    $item = Item::find($itemId);
                    if (! $item || ! $item->active) {
                        throw new \RuntimeException('الصنف غير موجود أو غير فعال');
                    }

                    $price = $this->price($itemId, $shift->location_id);
                    if ($price === null) {
                        throw new \RuntimeException('مفيش سعر لهذا الصنف في الفرع');
                    }

                    if ($this->available($itemId, $shift->location_id) < $qty) {
                        throw new \RuntimeException('المخزون غير كافٍ لهذا الصنف');
                    }

                    $lineTotal = round($qty * $price, 2);

                    $saleLines[] = ['line' => $index, 'item_id' => $itemId, 'box_sn' => null, 'qty' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
                    $moves[] = ['item_id' => $itemId, 'location_id' => $shift->location_id, 'qty' => -$qty, 'kind' => 'sale'];
                    $sub += $lineTotal;
                }
            }

            $total = round($sub - $discount, 2);
            if ($total < 0) {
                throw new \RuntimeException('الخصم أكبر من قيمة الفاتورة');
            }
            if (round($cash + $card, 2) < $total) {
                throw new \RuntimeException('المدفوع أقل من الإجمالي');
            }
            if ($card > $total) {
                throw new \RuntimeException('الفيزا أكتر من الإجمالي');
            }

            $change = round($cash + $card - $total, 2);
            $no = (int) max(100000, (int) Sale::max('no')) + 1;

            $sale = Sale::create([
                'no' => $no,
                'location_id' => $shift->location_id,
                'shift_id' => $shift->id,
                'cashier' => $userId,
                'at' => now(),
                'subtotal' => round($sub, 2),
                'discount' => round($discount, 2),
                'total' => $total,
                'paid_cash' => round($cash, 2),
                'paid_card' => round($card, 2),
                'change' => $change,
                'idempotency_key' => $idempotencyKey,
                'request_payload' => $payload,
                'discount_by' => $discount > 0 ? $userId : null,
            ]);

            foreach ($saleLines as &$sl) {
                $sl['sale_id'] = $sale->id;
            }
            unset($sl);
            SaleLine::insert($saleLines);

            foreach ($moves as &$move) {
                $move['ref'] = 'S'.$sale->no;
                $move['by'] = $userId;
                $move['at'] = now();
                $move['shift_id'] = $shift->id;
            }
            unset($move);
            StockMove::insert($moves);

            return $sale->fresh();
        });
    }

    public function closeShift(int $shiftId, float $counted, string $userId, ?string $notes = null): float
    {
        return DB::transaction(function () use ($shiftId, $counted, $userId, $notes) {
            $shift = Shift::where('id', $shiftId)->where('status', 'open')->lockForUpdate()->first();

            if (! $shift) {
                throw new \RuntimeException('الوردية دي مش مفتوحة');
            }

            if ($shift->cashier !== $userId) {
                $actor = \App\Models\User::find($userId);
                if (! $actor || ! $actor->hasPermission('pos.shift')) {
                    throw new \RuntimeException('مش مسموح بإغلاق وردية غيرك');
                }
            }

            $salesCash = (float) Sale::where('shift_id', $shiftId)->sum(DB::raw('paid_cash - `change`'));
            $shiftExpenses = (float) DB::table('expenses')->where('shift_id', $shiftId)->where('status', '!=', 'rejected')->sum('amount');
            $expected = round((float) $shift->opening_cash + $salesCash - $shiftExpenses, 2);

            $shift->update([
                'status' => 'closed',
                'open_cashier' => null,
                'closed_by' => $userId,
                'closed_at' => now(),
                'logged_out_at' => now(),
                'counted_cash' => $counted,
                'expected_cash' => $expected,
                'close_notes' => $notes,
            ]);

            return round($counted - $expected, 2);
        });
    }
}