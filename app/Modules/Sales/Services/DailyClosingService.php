<?php

namespace App\Modules\Sales\Services;

use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Models\Location;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Daily closing report (إقفال اليومية), per branch per day.
 *
 * The manager's rule: the system already calculates the cash from the
 * orders — the report shows the expected custody, it never asks the user
 * how much is in the drawer. Returns are shown separately from sales:
 * e.g. 10 orders sold and 1 returned, net is 9, but the report writes
 * the 10 AND writes the return so the accounts are correct.
 */
class DailyClosingService
{
    public function report(string $date, ?string $locationId): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $location = $locationId
            ? Location::find($locationId)
            : Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name')->first();

        $locationId = $location?->id;

        $shifts = Shift::where('location_id', $locationId)
            ->whereDate('opened_at', $day)
            ->orderBy('opened_at')
            ->get();
        $shiftIds = $shifts->pluck('id')->all();

        // ---- Orders of the day, each with its collection method ----
        $posSales = Sale::with(['customer', 'cashierUser'])
            ->where('location_id', $locationId)
            ->whereDate('at', $day)
            ->orderBy('at')
            ->get();

        $onlineOrders = OnlineOrder::with(['customer', 'driverRecord'])
            ->where('location_id', $locationId)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', $day)
            ->orderBy('delivered_at')
            ->get();

        $orders = [];
        foreach ($posSales as $sale) {
            $cashNet = round((float) $sale->paid_cash - (float) $sale->change, 2);
            $card = round((float) $sale->paid_card, 2);
            $orders[] = [
                'kind' => 'pos',
                'kind_label' => 'بيع مباشر (كاشير)',
                'no' => $sale->no,
                'time' => $sale->at,
                'customer' => $sale->customer?->name,
                'total' => round((float) $sale->total, 2),
                'cash' => $cashNet,
                'noncash' => $card,
                'method_label' => $cashNet > 0 && $card > 0 ? 'كاش + فيزا' : ($card > 0 ? 'فيزا' : 'كاش'),
            ];
        }
        foreach ($onlineOrders as $order) {
            $total = round((float) $order->total, 2);
            $isCash = $order->collection_method === 'cash';
            $orders[] = [
                'kind' => 'online',
                'kind_label' => 'أونلاين',
                'no' => $order->no,
                'time' => $order->delivered_at,
                'customer' => $order->customer?->name,
                'total' => $total,
                'cash' => $isCash ? $total : 0.0,
                'noncash' => $isCash ? 0.0 : $total,
                'method_label' => match ($order->collection_method) {
                    'cash' => 'كاش',
                    'instapay' => 'إنستا باي',
                    'card' => 'فيزا / محفظة',
                    default => $order->collection_method ?? '—',
                },
            ];
        }
        usort($orders, fn ($a, $b) => strcmp((string) $a['time'], (string) $b['time']));

        // ---- Returns of the day, shown separately ----
        $returns = SalesReturn::with('sale')
            ->where('location_id', $locationId)
            ->where('status', 'approved')
            ->whereDate('decided_at', $day)
            ->orderBy('decided_at')
            ->get();
        $returnsTotal = round((float) $returns->sum('total'), 2);
        $cashRefunds = round((float) $returns->where('refund_method', 'cash')->sum('refund_amount'), 2);
        $cardRefunds = round((float) $returns->where('refund_method', 'card')->sum('refund_amount'), 2);

        // ---- Money ----
        $gross = round(array_sum(array_column($orders, 'total')), 2);
        $cashCollected = round(array_sum(array_column($orders, 'cash')) - $cashRefunds, 2);
        $nonCashCollected = round(array_sum(array_column($orders, 'noncash')) - $cardRefunds, 2);
        $net = round($gross - $returnsTotal, 2);

        $shiftExpenses = $shiftIds === []
            ? 0.0
            : (float) ShiftExpense::whereIn('shift_id', $shiftIds)->whereIn('status', ['approved', 'pending'])->sum('amount');
        $generalExpenses = (float) DB::table('expenses')
            ->where('location_id', $locationId)
            ->whereDate('day', $day)
            ->where('status', '!=', 'rejected')
            ->sum('amount');
        $expenses = round($shiftExpenses + $generalExpenses, 2);

        $transfers = $shiftIds === []
            ? 0.0
            : (float) CashTransfer::whereIn('from_shift_id', $shiftIds)
                ->whereIn('type', ['to_manager', 'to_safe'])
                ->whereIn('status', ['approved', 'pending'])
                ->sum('amount');
        $transfers = round($transfers, 2);

        // Custody carried into the day: the last shift closed before the
        // day started in this branch. Cumulative: yesterday 2000 + today
        // 1000 = 3000 held.
        $openingCustody = 0.0;
        $lastClosed = Shift::where('location_id', $locationId)
            ->where('status', 'closed')
            ->where('closed_at', '<', $day)
            ->orderByDesc('closed_at')
            ->first();
        if ($lastClosed) {
            $openingCustody = round((float) $lastClosed->carryover_to_next_shift, 2);
        }

        $expectedCustody = round($openingCustody + $cashCollected - $expenses - $transfers, 2);

        // ---- Day window: first to last activity ----
        $times = array_filter(array_merge(
            $shifts->pluck('opened_at')->all(),
            $shifts->pluck('closed_at')->all(),
            array_column($orders, 'time'),
        ));
        $fromTime = $times === [] ? null : min($times);
        $toTime = $times === [] ? null : max($times);

        return [
            'date' => $day->toDateString(),
            'location' => $location,
            'location_id' => $locationId,
            'from_time' => $fromTime,
            'to_time' => $toTime,
            'shifts' => $shifts,
            'orders' => $orders,
            'returns' => $returns,
            'orders_count' => count($orders),
            'pos_count' => (int) $posSales->count(),
            'online_count' => (int) $onlineOrders->count(),
            'returns_count' => (int) $returns->count(),
            'gross' => $gross,
            'returns_total' => $returnsTotal,
            'net' => $net,
            'cash_collected' => $cashCollected,
            'noncash_collected' => $nonCashCollected,
            'cash_refunds' => $cashRefunds,
            'card_refunds' => $cardRefunds,
            'expenses' => $expenses,
            'transfers' => $transfers,
            'opening_custody' => $openingCustody,
            'expected_custody' => $expectedCustody,
        ];
    }
}
