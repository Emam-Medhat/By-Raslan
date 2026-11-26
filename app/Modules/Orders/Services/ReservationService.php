<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Price;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\CustomerAddress;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\Reservation;
use App\Modules\Orders\Models\ReservationLine;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reservations (الحجوزات) — Phase 3b.
 *
 * Two mechanisms convert a reservation into a real order, both go through
 * the same guarded convert() so a reservation can never convert twice:
 *
 *  1. Automatic: the scheduler runs convertDueToday() daily at 00:00
 *     (see bootstrap/app.php withSchedule). Pending reservations whose
 *     reservation_date is today become OnlineOrders.
 *  2. Manual: staff press "حوّل اوردر" on the reservation after confirming
 *     with the customer that morning/day.
 *
 * The nightly factory report aggregates quantities per item across all
 * reservations for tomorrow (pending + already converted, never cancelled)
 * — that is what the factory must prepare — with the reservations' notes
 * underneath. It is generated at 00:00 by the scheduler and stored in
 * factory_reports; no e-mail is sent yet (no factory e-mail is configured).
 */
class ReservationService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Staff creates a reservation for a future day.
     *
     * Branch: explicit choice wins, otherwise auto-picked from the
     * customer's area (same rule as createStaffOrder). Date must be today
     * or later — a reservation for "tomorrow" is the normal after-hours
     * case (orders taken after 4pm are told "your order comes tomorrow").
     *
     * @param list<array{item_id:int,qty:float}> $lines
     */
    public function create(array $data, string $userId): Reservation
    {
        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        if (strlen($phone) < 10 || strlen($phone) > 13) {
            throw new \RuntimeException('رقم الموبايل مش صحيح');
        }
        if (trim((string) ($data['name'] ?? '')) === '' || trim((string) ($data['address'] ?? '')) === '') {
            throw new \RuntimeException('اكتب الاسم والعنوان');
        }

        $date = Carbon::parse($data['reservation_date'] ?? '')->startOfDay();
        if ($date->lt(today())) {
            throw new \RuntimeException('تاريخ الحجز لازم يكون النهاردة أو بعد كده');
        }

        $lines = $data['lines'] ?? [];
        if ($lines === [] || count($lines) > 40) {
            throw new \RuntimeException('السلة فاضية');
        }

        $payMethod = $data['pay_method'] ?? 'cash';
        if (! in_array($payMethod, ['cash', 'card', 'instapay'], true)) {
            throw new \RuntimeException('طريقة الدفع مش صحيحة');
        }

        $location = $this->orders->resolveBranch($data);

        return DB::transaction(function () use ($data, $phone, $lines, $location, $payMethod, $date, $userId) {
            $customer = Customer::updateOrCreate(
                ['phone' => $phone],
                ['name' => trim((string) $data['name']), 'address' => trim((string) $data['address']), 'area' => $data['area'] ?? null],
            );

            CustomerAddress::firstOrCreate(
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

            $reservation = Reservation::create([
                'customer_id' => $customer->id,
                'location_id' => $location,
                'reservation_date' => $date->toDateString(),
                'status' => 'pending',
                'delivery_fee' => $fee,
                'pay_method' => $payMethod,
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
            ]);

            $index = 0;
            foreach ($lines as $line) {
                $index++;
                $qty = (float) $line['qty'];
                if ($qty <= 0 || $qty > 50) {
                    throw new \RuntimeException('الكمية مش صحيحة');
                }

                ReservationLine::create([
                    'reservation_id' => $reservation->id,
                    'line' => $index,
                    'item_id' => (int) $line['item_id'],
                    'qty' => $qty,
                    'unit_price' => Price::where('item_id', (int) $line['item_id'])
                        ->where('location_id', $location)->where('channel', 'app')->value('price'),
                ]);
            }

            return $reservation->fresh();
        });
    }

    /**
     * Convert one reservation into a real OnlineOrder.
     * Guarded: only a pending reservation converts; a second call returns
     * the already-created order instead of creating a duplicate.
     */
    public function convert(Reservation $reservation, string $userId): OnlineOrder
    {
        return DB::transaction(function () use ($reservation, $userId) {
            $reservation = Reservation::where('id', $reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status === 'converted' && $reservation->converted_order_id) {
                return OnlineOrder::findOrFail($reservation->converted_order_id);
            }
            if ($reservation->status !== 'pending') {
                throw new \RuntimeException('الحجز ده مش معلق — مينفعش يتحول لأوردر');
            }

            $reservation->load('customer', 'lines');

            // Reuse the staff-order path: same pricing (channel 'app'),
            // same branch, no min-order / 3-active-orders cap.
            $order = $this->orders->createStaffOrder([
                'phone' => $reservation->customer->phone,
                'name' => $reservation->customer->name,
                'address' => $reservation->customer->address ?? '',
                'area' => $reservation->customer->area,
                'location_id' => $reservation->location_id,
                'delivery_fee' => (float) $reservation->delivery_fee,
                'pay_method' => $reservation->pay_method,
                'note' => $reservation->note,
                'lines' => $reservation->lines->map(fn ($l) => [
                    'item_id' => (int) $l->item_id,
                    'qty' => (float) $l->qty,
                ])->all(),
            ], $userId);

            $reservation->update([
                'status' => 'converted',
                'converted_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            return $order;
        });
    }

    /** Scheduler (00:00): convert every pending reservation dated today. */
    public function convertDueToday(): array
    {
        $converted = [];
        $failed = [];

        $due = Reservation::where('status', 'pending')
            ->whereDate('reservation_date', today())
            ->orderBy('id')
            ->get();

        foreach ($due as $reservation) {
            try {
                // The auto-conversion runs as the system, not a user.
                $order = $this->convert($reservation, 'system');
                $converted[] = ['reservation_id' => $reservation->id, 'order_no' => $order->no];
            } catch (\Throwable $e) {
                $failed[$reservation->id] = $e->getMessage();
                Log::warning('فشل تحويل حجز تلقائي', ['reservation_id' => $reservation->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('تحويل الحجوزات التلقائي', ['date' => today()->toDateString(), 'converted' => count($converted), 'failed' => count($failed)]);

        return ['converted' => $converted, 'failed' => $failed];
    }

    public function cancel(Reservation $reservation): void
    {
        $reservation = Reservation::where('id', $reservation->id)->lockForUpdate()->firstOrFail();

        if ($reservation->status !== 'pending') {
            throw new \RuntimeException('الحجز ده مش معلق — مينفعش يتلغي');
        }

        $reservation->update(['status' => 'cancelled']);
    }

    /**
     * Factory report for a date: per-item aggregated quantities across
     * all reservations for that date (pending + already converted, never
     * cancelled), with the reservations and their notes underneath.
     *
     * @return array{date:string,items:list<array{item_id:int,name:string,unit:string,total_qty:float,reservations:int,branches:array}>,reservations:\Illuminate\Database\Eloquent\Collection,total_qty:float}
     */
    public function factoryReport(string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();

        $reservations = Reservation::with(['customer', 'lines.item', 'location'])
            ->whereDate('reservation_date', $day)
            ->whereIn('status', ['pending', 'converted'])
            ->orderBy('location_id')->orderBy('id')
            ->get();

        $items = [];
        foreach ($reservations as $reservation) {
            foreach ($reservation->lines as $line) {
                $id = (int) $line->item_id;
                $items[$id] ??= [
                    'item_id' => $id,
                    'name' => $line->item?->name ?? ('صنف #'.$id),
                    'unit' => $line->item?->unit ?? '',
                    'total_qty' => 0.0,
                    'reservations' => 0,
                    'branches' => [],
                ];
                $items[$id]['total_qty'] = round($items[$id]['total_qty'] + (float) $line->qty, 3);
                $items[$id]['reservations']++;
                $branch = $reservation->location_id;
                $items[$id]['branches'][$branch] = round(($items[$id]['branches'][$branch] ?? 0) + (float) $line->qty, 3);
            }
        }

        ksort($items);

        return [
            'date' => $day->toDateString(),
            'items' => array_values($items),
            'reservations' => $reservations,
            'total_qty' => round(array_sum(array_column(array_values($items), 'total_qty')), 3),
        ];
    }

    /** Scheduler (00:00): build + store tomorrow's factory report. */
    public function generateFactoryReportForTomorrow(): array
    {
        $report = $this->factoryReport(today()->addDay()->toDateString());

        DB::table('factory_reports')->updateOrInsert(
            ['date' => $report['date']],
            ['payload' => json_encode($report, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()],
        );

        Log::info('تقرير المصنع لحجوزات الغد', ['date' => $report['date'], 'items' => count($report['items']), 'reservations' => $report['reservations']->count()]);

        return $report;
    }

    public function lastGeneratedFactoryReport(?string $date): ?array
    {
        $row = DB::table('factory_reports')->where('date', $date ?? today()->addDay()->toDateString())->first();

        return $row ? ['generated_at' => $row->updated_at, 'payload' => json_decode($row->payload, true)] : null;
    }
}
