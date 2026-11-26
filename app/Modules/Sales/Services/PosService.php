<?php

namespace App\Modules\Sales\Services;

use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\ItemBarcode;
use App\Modules\Catalog\Models\Price;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\Driver;
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
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function openShift(string $location, float $cash, string $userId): Shift
    {
        if ($cash < 0 || $cash > 9_999_999_999.99 || round($cash, 2) != $cash) {
            throw new \RuntimeException('رصيد بداية الوردية غير صحيح');
        }

        $user = \App\Models\User::find($userId);
        if ($user && !$user->isOwner() && !$user->canAccessLocation($location)) {
            throw new \RuntimeException('ليس لديك صلاحية على هذا الفرع');
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

        return app(ShiftService::class)->create([
            'location_id' => $location,
            'cashier' => $userId,
            'opening_cash' => round($cash, 2),
        ]);

        // Cumulative custody: opening with the carried-over amount links
        // the previous shift's carryover transfer to this shift.
        $this->shifts->consumeCarryover($shift);

        return $shift;
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

        // Dish QR / UID / legacy box serial
        $box = null;
        if (preg_match('/^\d{8,20}$/', $code)) {
            $box = Box::with('item')->where('uid', $code)->first();
            if (! $box) $box = Box::with('item')->find((int) $code);
        }
        if (! $box && preg_match('/^R?\d{1,9}(\|.*)?$/', $upper) && ! ItemBarcode::where('code', $code)->exists()) {
            $sn = (int) preg_replace('/\D+/', '', explode('|', $upper)[0]);
            $box = Box::with('item')->find($sn);
        }
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
                $label = $box->uid ?: $box->sn;
                throw new \RuntimeException("الطبق #{$label} {$msg}");
            }
            if ($box->location_id !== $location) {
                $label = $box->uid ?: $box->sn;
                throw new \RuntimeException("الطبق #{$label} مش في الفرع ده");
            }
            if (! $box->item || ! $box->item->active) {
                throw new \RuntimeException('الصنف غير فعال');
            }
            if ((float) $box->weight <= 0) {
                throw new \RuntimeException('وزن الطبق غير صحيح');
            }
            $price = $this->price($box->item_id, $location);
            if ($price === null || $price < 0) {
                throw new \RuntimeException('مفيش سعر صالح لهذا الصنف في الفرع');
            }
            $qty = ($box->item->unit === 'قطعة' || ($box->item->sold_by ?? null) === 'piece') ? 1.0 : (float) $box->weight;
            return [
                'item_id' => $box->item_id,
                'item_name' => $box->item->name,
                'unit' => $box->item->unit,
                'box_sn' => $box->sn,
                'box_uid' => $box->uid,
                'qty' => $qty,
                'unit_price' => $price,
                'weight' => (float) $box->weight,
            ];
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

    private function checkDiscount(?\App\Models\User $actor, float $discount): void
    {
        if ($discount <= 0) {
            return;
        }
        if (! $actor || ! $actor->hasPermission('pos.discount')) {
            throw new \RuntimeException('الخصم محتاج صلاحية');
        }
        $limit = $actor->permissionLimit('pos.discount');
        if ($limit !== null && $discount > $limit) {
            throw new \RuntimeException("الخصم ({$discount} ج) أكبر من حدك ({$limit} ج)");
        }
    }

    /**
     * Resolve the optional driver for a direct sale.
     * Rule: a driver is required only when a delivery fee is charged
     * (delivery requested); walk-in sales (fee = 0) may have no driver.
     * The driver must be active and linked to the sale's branch.
     *
     * @return array{0:int|null,1:string|null} [driver_id, driver name]
     */
    private function resolveSaleDriver(?int $driverId, float $deliveryFee, string $locationId): array
    {
        if ($driverId !== null) {
            $driver = Driver::where('id', $driverId)->where('active', true)->first();
            if (! $driver || $driver->location_id !== $locationId) {
                throw new \RuntimeException('الطيار مش مربوط بالفرع ده');
            }

            return [(int) $driver->id, $driver->name];
        }

        if ($deliveryFee > 0) {
            throw new \RuntimeException('اختار الطيار عشان فيه رسوم توصيل');
        }

        return [null, null];
    }

    /**
     * "تجهيز" (Prepare): read-only invoice preview for a direct sale.
     * Validates the cart, discount, customer and driver exactly like
     * sale() does, but writes nothing and changes no stock/box status.
     * The cashier reviews the preview, then pays via sale().
     *
     * @param  list<array{box_sn?:int,item_id?:int,qty?:float}>  $lines
     * @return array{lines:list<array{item_name:string,box_sn:int|null,qty:float,unit_price:float,total:float}>,subtotal:float,discount:float,delivery_fee:float,total:float,customer_name:string|null,driver_name:string|null}
     */
    public function prepare(int $shiftId, array $lines, float $discount, array $options, string $userId): array
    {
        $shift = Shift::where('id', $shiftId)->where('cashier', $userId)->where('status', 'open')->first();
        if (! $shift) {
            throw new \RuntimeException('افتح وردية الأول');
        }
        if ($lines === []) {
            throw new \RuntimeException('الفاتورة فاضية');
        }
        if ($discount < 0 || round($discount, 2) != $discount) {
            throw new \RuntimeException('راجع قيم الخصم والمدفوع');
        }

        $actor = \App\Models\User::find($userId);
        $this->checkDiscount($actor, $discount);

        $deliveryFee = round((float) ($options['delivery_fee'] ?? 0), 2);
        if ($deliveryFee < 0) {
            throw new \RuntimeException('رسوم التوصيل مش صحيحة');
        }

        $customer = null;
        if (! empty($options['customer_id'])) {
            $customer = Customer::find((int) $options['customer_id']);
            if (! $customer) {
                throw new \RuntimeException('العميل مش موجود');
            }
        }

        [, $driverName] = $this->resolveSaleDriver(
            isset($options['driver_id']) ? (int) $options['driver_id'] : null,
            $deliveryFee,
            $shift->location_id,
        );

        $previewLines = [];
        $sub = 0.0;
        $seen = [];
        foreach ($lines as $line) {
            if (isset($line['box_sn'])) {
                $sn = (int) $line['box_sn'];
                if (in_array($sn, $seen, true)) {
                    throw new \RuntimeException("العلبة #{$sn} متكررة في الفاتورة");
                }
                $seen[] = $sn;
                $box = Box::with('item')->where('sn', $sn)->first();
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
                $previewLines[] = ['item_name' => $box->item->name, 'box_sn' => $sn, 'qty' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
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
                $previewLines[] = ['item_name' => $item->name, 'box_sn' => null, 'qty' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
                $sub += $lineTotal;
            }
        }

        $total = round($sub - $discount + $deliveryFee, 2);
        if ($total < 0) {
            throw new \RuntimeException('الخصم أكبر من قيمة الفاتورة');
        }

        return [
            'lines' => $previewLines,
            'subtotal' => round($sub, 2),
            'discount' => round($discount, 2),
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'customer_name' => $customer?->name,
            'driver_name' => $driverName,
        ];
    }

    /**
     * @param  list<array{box_sn?:int,item_id?:int,qty?:float}>  $lines
     * @param  array{customer_id?:int,delivery_fee?:float,driver_id?:int}  $options
     */
    public function sale(int $shiftId, array $lines, float $discount, float $cash, float $card, string $idempotencyKey, string $userId, array $meta = []): Sale
    {
        $deliveryFee = (float) ($meta['delivery_fee'] ?? 0);
        $payMethod = (string) ($meta['pay_method'] ?? '');
        foreach (['discount' => $discount, 'cash' => $cash, 'card' => $card, 'delivery_fee' => $deliveryFee] as $name => $value) {
            if (! is_finite($value) || $value < 0 || $value > 9_999_999_999.99 || round($value, 2) != $value) {
                throw new \RuntimeException('راجع قيم الخصم والمدفوع/التوصيل');
            }
        }
        if ($payMethod === '') {
            if ($card > 0 && $cash <= 0) $payMethod = 'card';
            elseif ($cash > 0 && $card <= 0) $payMethod = 'cash';
            elseif ($cash > 0 && $card > 0) $payMethod = 'mixed';
            else $payMethod = 'cash';
        }
        if (! in_array($payMethod, ['cash','card','instapay','wallet','mixed'], true)) {
            throw new \RuntimeException('طريقة التحصيل غير صحيحة');
        }

        $deliveryFee = round((float) ($options['delivery_fee'] ?? 0), 2);
        if ($deliveryFee < 0 || $deliveryFee > 9_999_999_999.99) {
            throw new \RuntimeException('رسوم التوصيل مش صحيحة');
        }
        $customerId = ! empty($options['customer_id']) ? (int) $options['customer_id'] : null;
        $driverIdOpt = isset($options['driver_id']) && $options['driver_id'] !== '' ? (int) $options['driver_id'] : null;

        $payload = [
            'shift' => $shiftId,
            'lines' => $lines,
            'discount' => round($discount, 2),
            'cash' => round($cash, 2),
            'card' => round($card, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'pay_method' => $payMethod,
            'meta' => $meta,
        ];

        return DB::transaction(function () use ($shiftId, $lines, $discount, $cash, $card, $idempotencyKey, $userId, $payload, $deliveryFee, $payMethod) {
            $shift = Shift::where('id', $shiftId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();
            if ($shift && $shift->cashier !== $userId) {
                $actor = \App\Models\User::find($userId);
                if (!$actor || !$actor->hasPermission('pos.shift')) {
                    throw new \RuntimeException('افتح وردية الأول');
                }
            }

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

            $this->checkDiscount($actor, $discount);

            $customer = null;
            if ($customerId !== null) {
                $customer = Customer::find($customerId);
                if (! $customer) {
                    throw new \RuntimeException('العميل مش موجود');
                }
            }

            [$resolvedDriverId] = $this->resolveSaleDriver($driverIdOpt, $deliveryFee, $shift->location_id);

            $sub = 0.0;
            $index = 0;
            $seen = [];
            $saleLines = [];
            $moves = [];
            $soldBoxes = [];

            foreach ($lines as $line) {
                $index++;

                $boxKey = $line['box_sn'] ?? $line['box_uid'] ?? null;
                if ($boxKey !== null && $boxKey !== '') {
                    $box = Box::with('item')->where(function($q) use ($boxKey) {
                        $q->where('uid', (string)$boxKey);
                        if (is_numeric($boxKey)) $q->orWhere('sn', (int)$boxKey);
                    })->lockForUpdate()->first();

                    if (! $box) {
                        throw new \RuntimeException("الطبق #{$boxKey} غير موجود");
                    }
                    $sn = (int) $box->sn;
                    if (in_array($sn, $seen, true)) {
                        $label = $box->uid ?: $box->sn;
                        throw new \RuntimeException("الطبق #{$label} متكرر في الفاتورة");
                    }
                    $seen[] = $sn;

                    if ($box->status !== 'stock' || $box->location_id !== $shift->location_id) {
                        $label = $box->uid ?: $box->sn;
                        throw new \RuntimeException("الطبق #{$label} لم يعد متاحًا للبيع من هذا الفرع");
                    }
                    if (! $box->item || ! $box->item->active) {
                        throw new \RuntimeException('الصنف غير فعال');
                    }

                    $price = $this->price($box->item_id, $shift->location_id);
                    if ($price === null) {
                        throw new \RuntimeException('مفيش سعر لصنف الطبق');
                    }

                    $qty = ($box->item->unit === 'قطعة' || ($box->item->sold_by ?? null) === 'piece') ? 1.0 : (float) $box->weight;
                    $lineTotal = round($qty * $price, 2);

                    $saleLines[] = ['line' => $index, 'item_id' => $box->item_id, 'box_sn' => $sn, 'qty' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
                    $moves[] = ['item_id' => $box->item_id, 'location_id' => $shift->location_id, 'qty' => -abs((float) $box->weight), 'kind' => 'sale', 'box_sn' => $sn];
                    $soldBoxes[] = $box;
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

            $total = round($sub - $discount + $deliveryFee, 2);
            if ($total < 0) {
                throw new \RuntimeException('الخصم أكبر من قيمة الفاتورة');
            }
            if (round($cash + $card, 2) < $total) {
                throw new \RuntimeException('المدفوع أقل من الإجمالي');
            }

            $change = round($cash + $card - $total, 2);
            $no = (int) max(100000, (int) Sale::max('no')) + 1;
            $paymentStatus = in_array($payMethod, ['cash']) ? 'cash_custody' : 'electronic';
            if ($payMethod === 'mixed') $paymentStatus = 'mixed';

            $sale = Sale::create([
                'no' => $no,
                'location_id' => $shift->location_id,
                'shift_id' => $shift->id,
                'cashier' => $userId,
                'customer_name' => $meta['customer_name'] ?? null,
                'customer_phone' => $meta['customer_phone'] ?? null,
                'at' => now(),
                'subtotal' => round($sub, 2),
                'discount' => round($discount, 2),
                'delivery_fee' => round($deliveryFee, 2),
                'total' => $total,
                'paid_cash' => round($cash, 2),
                'paid_card' => round($card, 2),
                'change' => $change,
                'pay_method' => $payMethod,
                'payment_status' => $paymentStatus,
                'collected_at' => now(),
                'collected_by' => $userId,
                'delivery_status' => ($meta['delivery'] ?? false) ? 'pending' : 'delivered',
                'driver_id' => $meta['driver_id'] ?? null,
                'order_source' => $meta['order_source'] ?? 'pos',
                'online_order_id' => $meta['online_order_id'] ?? null,
                'reservation_id' => $meta['reservation_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'request_payload' => $payload,
                'discount_by' => $discount > 0 ? $userId : null,
                'customer_id' => $customer?->id,
                'driver_id' => $resolvedDriverId,
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

            foreach ($soldBoxes as $box) {
                $box->update(['status' => 'sold', 'sold_at' => now(), 'sale_id' => $sale->id]);
            }

            return $sale->fresh();
        });
    }

    public function closeShift(int $shiftId, ?float $counted, string $userId, ?string $notes = null): float
    {
        return app(ShiftService::class)->closeShift($shiftId, $counted, $userId, $notes);
    }
}
