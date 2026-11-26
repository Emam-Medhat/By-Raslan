<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Orders\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservationService
{
    public function create(array $data, string $userId): Reservation
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = null;
            $phone = preg_replace('/\D+/', '', (string)($data['customer_phone'] ?? ''));
            if ($phone !== '' || !empty($data['customer_name'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $phone ?: ('R'.now()->format('YmdHis').rand(10,99))],
                    ['name' => $data['customer_name'] ?? 'عميل حجز', 'address' => $data['customer_address'] ?? null]
                );
                $customer->update(['name' => $data['customer_name'] ?? $customer->name, 'address' => $data['customer_address'] ?? $customer->address]);
            }

            $res = Reservation::create([
                'no' => (int) max(7000, (int) Reservation::max('no')) + 1,
                'location_id' => $data['location_id'],
                'customer_id' => $customer?->id,
                'customer_name' => $data['customer_name'] ?? $customer?->name,
                'customer_phone' => $data['customer_phone'] ?? $customer?->phone,
                'customer_address' => $data['customer_address'] ?? $customer?->address,
                'scheduled_for' => $data['scheduled_for'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $res->lines()->create([
                    'item_id' => (int) $line['item_id'],
                    'qty' => (float) $line['qty'],
                    'note' => $line['note'] ?? null,
                ]);
            }
            return $res;
        });
    }

    public function convertToOrder(Reservation $reservation, string $userId): OnlineOrder
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation = Reservation::where('id', $reservation->id)->lockForUpdate()->firstOrFail();
            if ($reservation->status === 'converted') throw new \RuntimeException('الحجز تم تحويله لأوردر بالفعل.');
            if ($reservation->status === 'cancelled') throw new \RuntimeException('الحجز ملغي.');
            $reservation->load('lines', 'customer');

            $customer = $reservation->customer;
            if (! $customer) {
                $phone = preg_replace('/\D+/', '', (string)$reservation->customer_phone) ?: ('R'.$reservation->id);
                $customer = Customer::firstOrCreate(['phone' => $phone], ['name' => $reservation->customer_name ?: 'عميل حجز', 'address' => $reservation->customer_address]);
            }

            $order = OnlineOrder::create([
                'no' => (int) max(9000, (int) OnlineOrder::max('no')) + 1,
                'token' => Str::random(40),
                'customer_id' => $customer->id,
                'location_id' => $reservation->location_id,
                'channel' => 'phone',
                'status' => 'new',
                'address' => $reservation->customer_address ?: $customer->address,
                'note' => 'تم إنشاؤه من الحجز #' . $reservation->no . ($reservation->notes ? ' - '.$reservation->notes : ''),
                'pay_method' => 'cash',
                'delivery_fee' => 0,
                'est_total' => 0,
                'reservation_id' => $reservation->id,
            ]);

            $est = 0; $i = 0;
            foreach ($reservation->lines as $line) {
                $i++;
                $price = (float) (\App\Modules\Catalog\Models\Price::where('item_id', $line->item_id)->where('location_id', $reservation->location_id)->where('channel','direct')->value('price')
                    ?? \App\Modules\Catalog\Models\Price::where('item_id', $line->item_id)->where('location_id', $reservation->location_id)->value('price')
                    ?? 0);
                OnlineOrderLine::create([
                    'order_id' => $order->id,
                    'line' => $i,
                    'item_id' => $line->item_id,
                    'qty' => $line->qty,
                    'unit_price' => $price,
                    'prepared' => 0,
                ]);
                $est += round(((float)$line->qty) * $price, 2);
            }
            $order->update(['est_total' => round($est, 2)]);

            $reservation->update([
                'status' => 'converted',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
                'online_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            return $order;
        });
    }
}
