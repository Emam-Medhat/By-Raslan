<?php

namespace App\Modules\Sales\Services;

use Illuminate\Support\Facades\DB;

class ShiftReportService
{
    public function report(array $f): array
    {
        $from = $f['from'] ?? now()->subDays(7)->toDateString();
        $to = $f['to'] ?? now()->toDateString();
        $location = $f['location'] ?? null;
        $cashier = $f['cashier'] ?? null;
        $status = $f['status'] ?? 'all';
        $search = trim((string) ($f['search'] ?? ''));
        $reconcile = $f['reconcile'] ?? 'all';
        $sort = $f['sort'] ?? 'opened';
        $desc = (bool) ($f['desc'] ?? true);
        $page = max(1, (int) ($f['page'] ?? 1));
        $pageSize = min(50, max(1, (int) ($f['page_size'] ?? 25)));

        $q = DB::table('shifts as sh')
            ->leftJoin('locations as loc', 'loc.id', '=', 'sh.location_id')
            ->leftJoin('users as u', 'u.id', '=', 'sh.cashier')
            ->leftJoin(DB::raw('(select shift_id, count(*) invoice_count, coalesce(sum(subtotal),0) sales_subtotal, coalesce(sum(discount),0) sales_discount, coalesce(sum(total),0) sales_total, coalesce(sum(paid_cash-`change`),0) cash_net, coalesce(sum(paid_card),0) card_total from sales group by shift_id) s'), 's.shift_id', '=', 'sh.id')
            ->leftJoin(DB::raw("(select shift_id, coalesce(sum(amount),0) shift_expenses from expenses where paid_from='shift' and status<>'rejected' group by shift_id) e"), 'e.shift_id', '=', 'sh.id')
            ->whereDate('sh.opened_at', '>=', $from)
            ->whereDate('sh.opened_at', '<=', $to)
            ->when($location, fn ($qq) => $qq->where('sh.location_id', $location))
            ->when($cashier, fn ($qq) => $qq->where('sh.cashier', $cashier))
            ->when(in_array($status, ['open', 'closed'], true), fn ($qq) => $qq->where('sh.status', $status))
            ->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    if (ctype_digit($search)) {
                        $w->orWhere('sh.id', (int) $search);
                    }
                    $w->orWhere('u.name', 'like', "%{$search}%");
                });
            })
            ->selectRaw('sh.*, loc.name as location_name, u.name as cashier_name, coalesce(s.invoice_count,0) invoice_count, coalesce(s.sales_subtotal,0) sales_subtotal, coalesce(s.sales_discount,0) sales_discount, coalesce(s.sales_total,0) sales_total, coalesce(s.cash_net,0) cash_net, coalesce(s.card_total,0) card_total, coalesce(e.shift_expenses,0) shift_expenses, case when sh.status="closed" and sh.counted_cash is not null and sh.expected_cash is not null then sh.counted_cash-sh.expected_cash else null end as difference');

        $rows = $q->get();
        $rows = $rows->filter(function ($r) use ($reconcile) {
            $d = $r->difference;
            return match ($reconcile) {
                'short' => $d !== null && (float) $d < 0,
                'over' => $d !== null && (float) $d > 0,
                'match' => $d !== null && (float) $d == 0.0,
                default => true,
            };
        })->values();

        $rows = $rows->sortBy(function ($r) use ($sort) {
            return match ($sort) {
                'sales' => (float) $r->sales_total,
                'diff' => abs((float) ($r->difference ?? 0)),
                default => strtotime((string) $r->opened_at) ?: 0,
            };
        }, SORT_REGULAR, $desc)->values();

        $summary = [
            'shift_count' => $rows->count(),
            'open_count' => $rows->where('status', 'open')->count(),
            'closed_count' => $rows->where('status', 'closed')->count(),
            'invoice_count' => (int) $rows->sum('invoice_count'),
            'sales_subtotal' => round((float) $rows->sum('sales_subtotal'), 2),
            'sales_discount' => round((float) $rows->sum('sales_discount'), 2),
            'sales_total' => round((float) $rows->sum('sales_total'), 2),
            'cash_net' => round((float) $rows->sum('cash_net'), 2),
            'card_total' => round((float) $rows->sum('card_total'), 2),
            'shift_expenses' => round((float) $rows->sum('shift_expenses'), 2),
            'shortage_total' => round((float) $rows->filter(fn ($r) => (float) ($r->difference ?? 0) < 0)->sum(fn ($r) => -((float) $r->difference)), 2),
            'overage_total' => round((float) $rows->filter(fn ($r) => (float) ($r->difference ?? 0) > 0)->sum('difference'), 2),
            'mismatch_count' => $rows->filter(fn ($r) => $r->difference !== null && (float) $r->difference != 0.0)->count(),
        ];

        $pageRows = $rows->forPage($page, $pageSize)->values();

        return ['summary' => $summary, 'rows' => $pageRows, 'total' => $rows->count(), 'page' => $page, 'page_size' => $pageSize];
    }
}
