<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController
{
    public function index(Request $request): View
    {
        $query = Shift::with(['cashierUser', 'location', 'closedBy'])->orderByDesc('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($w) use ($q) {
                $w->whereHas('cashierUser', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                    ->orWhere('id', $q);
            });
        }

        if ($branch = $request->input('branch')) {
            $query->where('location_id', $branch);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('opened_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('opened_at', '<=', $to);
        }

        $shifts = $query->paginate(20)->withQueryString();
        $branches = Location::orderBy('name')->get();

        return view('shifts.index', compact('shifts', 'branches'));
    }

    public function export()
    {
        $filename = 'shifts-'.now()->format('Y-m-d').'.csv';
        $shifts = Shift::with('cashierUser')->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($shifts) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم', 'الموظف', 'الفرع', 'الحالة', 'البداية', 'النهاية', 'رصيد البداية', 'المتوقع', 'المعدود']);
            foreach ($shifts as $s) {
                fputcsv($out, [
                    $s->id, $s->cashierUser?->name, $s->location_id, $s->status,
                    $s->opened_at, $s->closed_at, $s->opening_cash, $s->expected_cash, $s->counted_cash,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function movements(Shift $shift): View
    {
        $shift->load(['cashierUser', 'location', 'closedBy']);

        $sales = Sale::with('cashierUser')->where('shift_id', $shift->id)->orderBy('id')->get();
        $stockMoves = StockMove::with('user')->where('shift_id', $shift->id)->orderBy('id')->get();
        $expenses = Expense::with('category')->where('shift_id', $shift->id)->orderBy('id')->get();

        $cashSales = (float) $sales->sum('paid_cash');
        $cardSales = (float) $sales->sum('paid_card');
        $change = (float) $sales->sum('change');
        $expensesTotal = (float) $expenses->sum('amount');
        $netCash = $cashSales - $change - $expensesTotal;
        $expected = round((float) $shift->opening_cash + $netCash, 2);

        return view('shifts.movements', compact(
            'shift', 'sales', 'stockMoves', 'expenses',
            'cashSales', 'cardSales', 'change', 'expensesTotal', 'expected'
        ));
    }
}