<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Price;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderBox;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Services\PosService;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly PosService $pos,
    ) {
    }

    /** @return array<int,array<string,mixed>> */
    public function branches(): array
    {
        $fees = $this->settings->json('online.delivery_fee');
        $min = $this->settings->float('online.min_order');
        $open = $this->settings->bool('online.open', true);

        return Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('sort')->get()
            ->map(fn (Location $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'fee' => (float) ($fees[$l->id] ?? 0),
                'min_order' => $min,
                'open' => $open,
            ])->all();
    }

    /** @return array<int,array<string,mixed>> */
    public function menu(string $location): array
    {
        return Price::query()
            ->join('items', 'items.id', '=', 'prices.item_id')
            ->where('prices.location_id', $location)
            ->where('prices.channel', 'app')
            ->where('items.active', true)
            ->select('items.id as item_id', 'items.name', 'items.unit', 'prices.price')
            ->orderBy('items.name')
            ->get()->map(fn ($r) => ['item_id' => (int) $r->item_id, 'name' => $r->name, 'unit' => $r->unit, 'price' => (float) $r->price])
            ->all();
    }

    /** @param list<array{item_id:int,qty:float}> $lines @return array{no:int,token:string,est_total:float} */
    public function placeOrder(array $data): array
    {
        if (! $this->settings->bool('online.open', true)) {
            throw new \RuntimeException('الطلبات أونلاين مقفولة دلوقتي');
        }

        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            throw new \RuntimeException('رقم الموبايل مش صحيح');
        }
        if (trim((string) ($data['name'] ?? '')) === '' || trim((string) ($data['address'] ?? '')) === '') {
            throw new \RuntimeException('اكتب الاسم والعنوان');
        }

        $lines = $data['lines'] ?? [];
        if ($lines === [] || count($lines) > 40) {
            throw new \RuntimeException('السلة فاضية');
        }

        $location = $data['location_id'];
        if (! Location::where('id', $location)->whereIn('type', ['dark', 'shop'])->where('active', true)->exists()) {
            throw new \RuntimeException('اختار الفرع');
        }

        $active = OnlineOrder::whereHas('customer', fn ($q) => $q->where('phone', $phone))
            ->whereIn('status', ['new', 'accepted', 'preparing'])->count();
        if ($active >= 3) {
            throw new \RuntimeException('عندك طلبات لسه بتتحضر — كلمنا');
        }

        return DB::transaction(function () use ($data, $phone, $lines, $location) {
            $customer = Customer::updateOrCreate(
                ['phone' => $phone],
                ['name' => trim((string) $data['name']), 'address' => trim((string) $data['address']), 'area' => $data['area'] ?? null],
            );

            $fees = $this->settings->json('online.delivery_fee');
            $fee = (float) ($fees[$location] ?? 0);

            $order = OnlineOrder::create([
                'no' => (int) max(9000, (int) OnlineOrder::max('no')) + 1,
                'token' => Str::random(40),
                'customer_id' => $customer->id,
                'location_id' => $location,
                'channel' => $data['channel'] ?? 'web',
                'status' => 'new',
                'address' => trim((string) $data['address']),
                'note' => $data['note'] ?? null,
                'pay_method' => $data['pay'] ?? 'cash',
                'delivery_fee' => $fee,
                'est_total' => 0,
            ]);

            $est = 0.0;
            $index = 0;
            foreach ($lines as $line) {
                $index++;
                $price = Price::where('item_id', (int) $line['item_id'])
                    ->where('location_id', $location)->where('channel', 'app')->value('price');

                if ($price === null) {
                    throw new \RuntimeException('صنف مش متاح في الفرع ده');
                }

                $qty = (float) $line['qty'];
                if ($qty <= 0 || $qty > 50) {
                    throw new \RuntimeException('الكمية مش صحيحة');
                }

                OnlineOrderLine::create([
                    'order_id' => $order->id,
                    'line' => $index,
                    'item_id' => (int) $line['item_id'],
                    'qty' => $qty,
                    'unit_price' => (float) $price,
                    'prepared' => 0,
                ]);

                $est += round($qty * (float) $price, 2);
            }

            $min = $this->settings->float('online.min_order');
            if ($est < $min) {
                throw new \RuntimeException('أقل طلب '.$min.' ج');
            }

            $order->update(['est_total' => round($est, 2)]);

            return ['no' => $order->no, 'token' => $order->token, 'est_total' => round($est + $fee, 2)];
        });
    }

    public function track(string $token): OnlineOrder
    {
        return OnlineOrder::with(['lines.item', 'customer'])->where('token', $token)->firstOrFail();
    }

    public function setStatus(OnlineOrder $order, string $status, ?string $driver, ?string $reason, string $userId): void
    {
        $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

        if ($status === 'cancelled') {
            if (in_array($order->status, ['delivered', 'cancelled'], true)) {
                throw new \RuntimeException('الطلب اتقفل');
            }

            OnlineOrderBox::where('order_id', $order->id)->where('removed', false)->update(['removed' => true, 'active_box' => null]);
            $order->update(['status' => 'cancelled', 'cancel_reason' => $reason, 'handled_by' => $userId]);

            return;
        }

        if ($status === 'accepted' && $order->status === 'new') {
            $order->update(['status' => 'accepted', 'accepted_at' => now(), 'handled_by' => $userId]);

            return;
        }

        if ($status === 'ready' && in_array($order->status, ['accepted', 'preparing'], true)) {
            $has = OnlineOrderBox::where('order_id', $order->id)->where('removed', false)->exists()
                || OnlineOrderLine::where('order_id', $order->id)->where('prepared', '>', 0)->exists();

            if (! $has) {
                throw new \RuntimeException('امسح العلب الأول');
            }

            $order->update(['status' => 'ready', 'ready_at' => now()]);

            return;
        }

        if ($status === 'out' && $order->status === 'ready') {
            if (trim((string) $driver) === '') {
                throw new \RuntimeException('اكتب اسم الطيار');
            }

            $order->update(['status' => 'out', 'out_at' => now(), 'driver' => trim((string) $driver)]);

            return;
        }

        throw new \RuntimeException("مينفعش ينتقل من {$order->status} لـ {$status}");
    }

    public function scan(OnlineOrder $order, string $code, ?float $qty): string
    {
        $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

        if (! in_array($order->status, ['accepted', 'preparing'], true)) {
            throw new \RuntimeException('اقبل الطلب الأول');
        }

        $line = $this->pos->lookup($order->location_id, $code);

        $orderLine = OnlineOrderLine::where('order_id', $order->id)
            ->where('item_id', $line['item_id'])
            ->orderByRaw('(prepared >= qty) desc')
            ->orderBy('line')
            ->first();

        if (! $orderLine) {
            throw new \RuntimeException('الصنف مش في الطلب ده');
        }

        if ($line['box_sn'] !== null) {
            if (OnlineOrderBox::where('box_sn', $line['box_sn'])->where('removed', false)->exists()) {
                throw new \RuntimeException('العلبة محجوزة لطلب تاني');
            }

            OnlineOrderBox::create([
                'order_id' => $order->id,
                'box_sn' => $line['box_sn'],
                'line' => $orderLine->line,
                'qty' => $line['qty'],
                'at' => now(),
                'active_box' => $line['box_sn'],
            ]);

            $orderLine->update(['prepared' => $orderLine->prepared + $line['qty']]);
        } else {
            $orderLine->update(['prepared' => $orderLine->prepared + ($qty ?? 1)]);
        }

        if ($order->status === 'accepted') {
            $order->update(['status' => 'preparing']);
        }

        return $line['item_name'];
    }

    public function unscan(OnlineOrder $order, int $sn): void
    {
        $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

        if (! in_array($order->status, ['accepted', 'preparing', 'ready'], true)) {
            throw new \RuntimeException('الطلب خرج');
        }

        $box = OnlineOrderBox::where('order_id', $order->id)->where('box_sn', $sn)->where('removed', false)->first();

        if (! $box) {
            throw new \RuntimeException('العلبة دي مش في الطلب');
        }

        $box->update(['removed' => true, 'active_box' => null]);

        $line = OnlineOrderLine::where('order_id', $order->id)->where('line', $box->line)->first();
        if ($line) {
            $line->update(['prepared' => max($line->prepared - $box->qty, 0)]);
        }
    }

    public function deliver(OnlineOrder $order): float
    {
        return DB::transaction(function () use ($order) {
            $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'out') {
                throw new \RuntimeException('الطلب لازم يكون خرج مع الطيار');
            }

            foreach (OnlineOrderBox::where('order_id', $order->id)->where('removed', false)->get() as $ob) {
                $box = Box::where('sn', $ob->box_sn)->lockForUpdate()->first();

                if (! $box || $box->status !== 'stock') {
                    throw new \RuntimeException('علبة مش متاحة في التسليم');
                }

                $box->update(['status' => 'sold']);

                StockMove::create([
                    'item_id' => $box->item_id,
                    'location_id' => $order->location_id,
                    'qty' => -abs((float) $box->weight),
                    'kind' => 'sale',
                    'ref' => 'W'.$order->no,
                    'box_sn' => $box->sn,
                ]);
            }

            foreach (OnlineOrderLine::where('order_id', $order->id)->get() as $line) {
                $boxed = (float) OnlineOrderBox::where('order_id', $order->id)->where('line', $line->line)->where('removed', false)->sum('qty');
                $remaining = (float) $line->prepared - $boxed;

                if ($remaining > 0) {
                    StockMove::create([
                        'item_id' => $line->item_id,
                        'location_id' => $order->location_id,
                        'qty' => -$remaining,
                        'kind' => 'sale',
                        'ref' => 'W'.$order->no,
                    ]);
                }
            }

            $sub = (float) OnlineOrderLine::where('order_id', $order->id)->get()
                ->sum(fn ($l) => (float) $l->prepared * (float) $l->unit_price);
            $total = round($sub + (float) $order->delivery_fee, 2);

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'subtotal' => round($sub, 2),
                'total' => $total,
                'paid' => true,
            ]);

            return $total;
        });
    }
}