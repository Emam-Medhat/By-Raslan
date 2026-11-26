<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderBox;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleLine;
use App\Modules\Sales\Models\Shift;
use App\Modules\Inventory\Models\Box;
use Illuminate\Support\Facades\DB;

class OrderCollectionService
{
    public function collect(OnlineOrder $order, string $method, float $amount, string $userId, bool $delivered = true): OnlineOrder
    {
        if (!in_array($method, ['cash','instapay','visa','wallet'], true)) {
            throw new \RuntimeException('طريقة التحصيل غير صحيحة.');
        }
        if (!is_finite($amount) || $amount < 0 || $amount > 9_999_999_999.99 || round($amount, 2) != $amount) {
            throw new \RuntimeException('المبلغ غير صحيح.');
        }

        return DB::transaction(function () use ($order, $method, $amount, $userId, $delivered) {
            $order = OnlineOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if ($order->collected_at) throw new \RuntimeException('تم تحصيل هذا الأوردر بالفعل.');

            if ($order->status === 'out' && $delivered) {
                app(OrderService::class)->deliver($order);
                $order->refresh();
            }
            if ($order->status !== 'delivered') {
                throw new \RuntimeException('أكد استلام الأوردر من الطيار أولاً.');
            }

            $shift = Shift::where('cashier', $userId)
                ->where('location_id', $order->location_id)
                ->where('status','open')
                ->lockForUpdate()
                ->first();
            if (! $shift) throw new \RuntimeException('افتح وردية على نفس فرع الأوردر لتسجيل التحصيل.');

            $total = round((float) ($order->total ?? $order->est_total ?? 0), 2);
            if ($amount < $total) throw new \RuntimeException('المبلغ المحصل أقل من إجمالي الأوردر.');
            if ($method !== 'cash' && round($amount, 2) !== $total) {
                throw new \RuntimeException('التحصيل الإلكتروني يجب أن يساوي إجمالي الأوردر بالضبط.');
            }

            $sale = Sale::where('online_order_id', $order->id)->lockForUpdate()->first();
            if ($sale && $sale->lines()->exists()) {
                throw new \RuntimeException('للأوردر فاتورة سابقة؛ راجع الإدارة قبل إعادة التحصيل.');
            }
            $cash = $method === 'cash' ? $amount : 0.0;
            $card = $method !== 'cash' ? $amount : 0.0;
            $payMethod = $method === 'visa' ? 'card' : $method;

            if (! $sale) {
                $sale = Sale::create([
                    'no' => (int) max(100000, (int) Sale::max('no')) + 1,
                    'location_id' => $order->location_id,
                    'shift_id' => $shift->id,
                    'cashier' => $userId,
                    'customer_name' => $order->customer?->name,
                    'customer_phone' => $order->customer?->phone,
                    'at' => now(),
                    'subtotal' => (float) ($order->subtotal ?? $order->est_total ?? 0),
                    'discount' => 0,
                    'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                    'total' => $total,
                    'paid_cash' => 0,
                    'paid_card' => 0,
                    'change' => 0,
                    'pay_method' => $payMethod,
                    'payment_status' => 'pending',
                    'delivery_status' => 'delivered',
                    'delivered_at' => $order->delivered_at ?? now(),
                    'order_source' => $order->reservation_id ? 'reservation' : ($order->channel === 'web' ? 'app' : 'call_center'),
                    'online_order_id' => $order->id,
                    'reservation_id' => $order->reservation_id,
                    'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                    'request_payload' => ['collection' => true, 'order_id' => $order->id],
                ]);
            }

            $saleLines = [];
            $activeBoxes = OnlineOrderBox::where('order_id', $order->id)
                ->where('removed', false)
                ->orderBy('id')
                ->get()
                ->groupBy('line');
            $lineNumber = 0;
            foreach (OnlineOrderLine::where('order_id', $order->id)->orderBy('line')->get() as $orderLine) {
                $boxes = $activeBoxes->get($orderLine->line, collect());
                $boxedQty = (float) $boxes->sum('qty');
                $preparedQty = (float) $orderLine->prepared;
                if ($preparedQty <= 0) continue;
                if ($preparedQty + 0.001 < $boxedQty) {
                    throw new \RuntimeException('كمية الأطباق الممسوحة أكبر من الكمية المحضرة.');
                }

                foreach ($boxes as $orderBox) {
                    $box = Box::where('sn', $orderBox->box_sn)->lockForUpdate()->first();
                    if (! $box || $box->item_id !== $orderLine->item_id) {
                        throw new \RuntimeException('طبق غير صالح مرتبط بأحد أصناف الأوردر.');
                    }
                    if ($box->status !== 'sold' || ($box->sale_id && $box->sale_id !== $sale->id)) {
                        throw new \RuntimeException('حالة أحد أطباق الأوردر غير متوافقة مع التسليم.');
                    }

                    $lineNumber++;
                    $saleLines[] = [
                        'sale_id' => $sale->id,
                        'line' => $lineNumber,
                        'item_id' => $orderLine->item_id,
                        'box_sn' => $box->sn,
                        'qty' => $orderBox->qty,
                        'unit_price' => $orderLine->unit_price,
                        'total' => round((float) $orderBox->qty * (float) $orderLine->unit_price, 2),
                    ];
                    $box->update(['sale_id' => $sale->id]);
                }

                $looseQty = round($preparedQty - $boxedQty, 3);
                if ($looseQty > 0) {
                    $lineNumber++;
                    $saleLines[] = [
                        'sale_id' => $sale->id,
                        'line' => $lineNumber,
                        'item_id' => $orderLine->item_id,
                        'box_sn' => null,
                        'qty' => $looseQty,
                        'unit_price' => $orderLine->unit_price,
                        'total' => round($looseQty * (float) $orderLine->unit_price, 2),
                    ];
                }
            }
            if ($saleLines === []) throw new \RuntimeException('الأوردر لا يحتوي على أصناف مجهزة للتحصيل.');
            SaleLine::insert($saleLines);

            $sale->update([
                'pay_method' => $payMethod,
                'payment_status' => $method === 'cash' ? 'cash_custody' : 'electronic',
                'paid_cash' => $cash,
                'paid_card' => $card,
                'change' => $method === 'cash' ? round($cash - $total, 2) : 0,
                'collected_at' => now(),
                'collected_by' => $userId,
                'delivery_status' => 'delivered',
                'delivered_at' => $order->delivered_at ?? now(),
            ]);

            $order->update([
                'collection_method' => $method,
                'collected_amount' => $amount,
                'collected_by' => $userId,
                'collected_at' => now(),
                'paid' => true,
                'sale_id' => $sale->id,
            ]);

            return $order->fresh();
        });
    }
}
