<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Price;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\CustomerAddress;
use App\Modules\Orders\Models\Driver;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderBox;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftCollection;
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

    /**
     * Staff (customer-service) order creation.
     *
     * Same pricing as the public API (channel 'app'), but: the branch is
     * auto-picked from the customer's area -> linked address location
     * when the staff did not choose one explicitly; the delivery fee is
     * editable (defaults to the branch setting); the app min-order and
     * the 3-active-orders cap do NOT apply to staff orders.
     * Channel is stored as 'phone' (خدمة عملاء).
     *
     * @param list<array{item_id:int,qty:float}> $lines
     */
    public function createStaffOrder(array $data, string $userId): OnlineOrder
    {
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

        $payMethod = $data['pay_method'] ?? 'cash';
        if (! in_array($payMethod, ['cash', 'card', 'instapay'], true)) {
            throw new \RuntimeException('طريقة الدفع مش صحيحة');
        }

        $location = $this->resolveBranch($data);

        return DB::transaction(function () use ($data, $phone, $lines, $location, $payMethod, $userId) {
            $customer = Customer::updateOrCreate(
                ['phone' => $phone],
                ['name' => trim((string) $data['name']), 'address' => trim((string) $data['address']), 'area' => $data['area'] ?? null],
            );

            $address = CustomerAddress::firstOrCreate(
                ['customer_id' => $customer->id, 'address' => trim((string) $data['address'])],
                [
                    'label' => 'البيت',
                    'area' => $data['area'] ?? $customer->area,
                    'location_id' => $location,
                    'is_default' => ! CustomerAddress::where('customer_id', $customer->id)->exists(),
                    'active' => true,
                ],
            );

            $fees = $this->settings->json('online.delivery_fee');
            $fee = isset($data['delivery_fee'])
                ? round((float) $data['delivery_fee'], 2)
                : (float) ($fees[$location] ?? 0);
            if ($fee < 0) {
                throw new \RuntimeException('رسوم التوصيل مش صحيحة');
            }

            $order = OnlineOrder::create([
                'no' => (int) max(9000, (int) OnlineOrder::max('no')) + 1,
                'token' => Str::random(40),
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'location_id' => $location,
                'channel' => 'phone',
                'status' => 'new',
                'address' => trim((string) $data['address']),
                'note' => $data['note'] ?? null,
                'pay_method' => $payMethod,
                'delivery_fee' => $fee,
                'est_total' => 0,
                'handled_by' => $userId,
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

            $order->update(['est_total' => round($est, 2)]);

            return $order->fresh();
        });
    }

    /**
     * Branch for a staff order: explicit choice wins; otherwise the
     * customer's area -> a linked customer address (default first).
     * Throws when the area has no linked branch.
     */
    public function resolveBranch(array $data): string
    {
        if (! empty($data['location_id'])) {
            $location = (string) $data['location_id'];
            if (! Location::where('id', $location)->whereIn('type', ['dark', 'shop'])->where('active', true)->exists()) {
                throw new \RuntimeException('اختار الفرع');
            }

            return $location;
        }

        $area = trim((string) ($data['area'] ?? ''));
        if ($area !== '') {
            $address = CustomerAddress::where('area', $area)
                ->whereNotNull('location_id')->where('active', true)
                ->orderByDesc('is_default')->orderByDesc('id')->first();

            if ($address) {
                return (string) $address->location_id;
            }
        }

        throw new \RuntimeException('المنطقة دي مش مربوطة بفرع — اختار الفرع بنفسك');
    }

    /** @return array{0:int|null,1:string|null} [driver_id, driver display name] */
    public function resolveDriver(?int $driverId, ?string $driverName, string $locationId): array
    {
        if ($driverId !== null) {
            $driver = Driver::where('id', $driverId)->where('active', true)->first();

            if (! $driver || $driver->location_id !== $locationId) {
                throw new \RuntimeException('الطيار مش مربوط بالفرع ده');
            }

            return [(int) $driver->id, $driver->name];
        }

        // Legacy free-text driver name (old orders / seeds).
        $name = trim((string) $driverName);

        return [null, $name === '' ? null : $name];
    }

    public function track(string $token): OnlineOrder
    {
        return OnlineOrder::with(['lines.item', 'customer'])->where('token', $token)->firstOrFail();
    }

    public function setStatus(OnlineOrder $order, string $status, ?string $driver, ?string $reason, string $userId, ?int $driverId = null): void
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
            [$resolvedDriverId, $resolvedDriverName] = $this->resolveDriver($driverId, $driver, $order->location_id);

            if ($resolvedDriverName === null) {
                throw new \RuntimeException('اختار الطيار من قائمة طيارين الفرع');
            }

            $order->update([
                'status' => 'out',
                'out_at' => now(),
                'driver' => $resolvedDriverName,
                'driver_id' => $resolvedDriverId,
            ]);

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

    /**
     * Receiving / collection (الشاشة الرابعة): the order must be 'out'.
     * A collection method is required — it is never silently paid.
     * The amount must equal the order total. Cash is recorded as custody
     * of the receiving user on their open shift in this branch
     * (ShiftCollection, method=cash); instapay/card are recorded for
     * reports only and do NOT increase custody. Requires an open shift
     * for the receiver in this branch, otherwise the collection has no
     * shift/custody to attach to.
     */
    public function deliver(OnlineOrder $order, string $method, float $amount, string $userId): float
    {
        if (! in_array($method, OnlineOrder::COLLECTION_METHODS, true)) {
            throw new \RuntimeException('اختار طريقة التحصيل: كاش أو إنستا باي أو فيزا');
        }

        return DB::transaction(function () use ($order, $method, $amount, $userId) {
            $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'out') {
                throw new \RuntimeException('الطلب لازم يكون خرج مع الطيار');
            }

            $sub = (float) OnlineOrderLine::where('order_id', $order->id)->get()
                ->sum(fn ($l) => (float) $l->prepared * (float) $l->unit_price);
            $total = round($sub + (float) $order->delivery_fee, 2);

            if (abs(round($amount, 2) - $total) > 0.01) {
                throw new \RuntimeException('المبلغ لازم يساوي إجمالي الطلب ('.$total.' ج)');
            }

            $shift = Shift::where('cashier', $userId)
                ->where('location_id', $order->location_id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                throw new \RuntimeException('افتح وردية في الفرع ده الأول عشان التحصيل يتسجل على عهدتك');
            }

            foreach (OnlineOrderBox::where('order_id', $order->id)->where('removed', false)->get() as $ob) {
                $box = Box::where('sn', $ob->box_sn)->lockForUpdate()->first();

                if (! $box || $box->status !== 'stock') {
                    throw new \RuntimeException('علبة مش متاحة في التسليم');
                }

                $box->update(['status' => 'sold', 'sold_at' => now()]);

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

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'subtotal' => round($sub, 2),
                'total' => $total,
                'paid' => false,
            ]);

            return $total;
        });
    }
}
