<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftExpense;
use App\Modules\Sales\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'location', 'status', 'from', 'to', 'user_id']);

        // Map filter names for service
        $serviceFilters = $filters;
        if (isset($filters['user_id'])) {
            $serviceFilters['cashier'] = $filters['user_id'];
        }
        if (isset($filters['from'])) {
            $serviceFilters['date_from'] = $filters['from'];
        }
        if (isset($filters['to'])) {
            $serviceFilters['date_to'] = $filters['to'];
        }

        $shifts = $this->shifts->getPaginated($serviceFilters);
        $stats = $this->shifts->getStats();
        
        // Add total variance to stats
        $closedShifts = Shift::where('status', 'closed')
            ->whereNotNull('counted_cash')
            ->whereNotNull('expected_cash')
            ->get(['counted_cash', 'expected_cash']);
        $totalVariance = 0;
        foreach ($closedShifts as $s) {
            $totalVariance += round((float) $s->counted_cash - (float) $s->expected_cash, 2);
        }
        $stats['total_variance'] = $totalVariance;

        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('shifts.index', compact('shifts', 'stats', 'locations', 'users', 'filters'));
    }

    public function create(): View
    {
        $locations = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name')->get();
        $users = User::where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('shifts.create', compact('locations', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'cashier' => ['required', 'exists:users,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->shifts->create($validated);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('shifts.index')->with('status', 'تم فتح الوردية');
    }

    public function show(Shift $shift): View
    {
        $shift->load(['cashierUser', 'location', 'closedBy']);

        $sales = Sale::with('cashierUser')->where('shift_id', $shift->id)->orderBy('id')->get();
        $shiftExpenses = ShiftExpense::with(['user', 'approver'])->where('shift_id', $shift->id)->orderBy('id')->get();
        $cashTransfers = CashTransfer::with(['fromUser', 'toUser', 'approver'])->where('from_shift_id', $shift->id)->orderBy('id')->get();
        $stockMoves = StockMove::with('user')->where('shift_id', $shift->id)->orderBy('id')->get();
        $expenses = Expense::with('category')->where('shift_id', $shift->id)->orderBy('id')->get();

        $cashSales = (float) $sales->sum('paid_cash');
        $cardSales = (float) $sales->sum('paid_card');
        $change = (float) $sales->sum('change');
        $shiftExpensesTotal = (float) $shiftExpenses->sum('amount');
        $expensesTotal = (float) $expenses->sum('amount');
        $transfersTotal = (float) $cashTransfers->sum('amount');
        $netCash = $cashSales - $change - $shiftExpensesTotal - $transfersTotal;
        $expected = round((float) $shift->opening_cash + $netCash, 2);
        $difference = $shift->counted_cash !== null ? round((float) $shift->counted_cash - $expected, 2) : null;

        return view('shifts.show', compact(
            'shift', 'sales', 'shiftExpenses', 'cashTransfers', 'stockMoves', 'expenses',
            'cashSales', 'cardSales', 'change', 'shiftExpensesTotal', 'expensesTotal',
            'transfersTotal', 'expected', 'difference'
        ));
    }

    public function edit(Shift $shift): View
    {
        $locations = Location::orderBy('name')->get();
        $cashiers = User::orderBy('name')->get(['id', 'name']);

        return view('shifts.edit', compact('shift', 'locations', 'cashiers'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift->update($validated);

        return redirect()->route('shifts.show', $shift)->with('status', 'تم تحديث الوردية');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->status === 'open') {
            return back()->withErrors(['shift' => 'لا يمكن حذف وردية مفتوحة']);
        }

        $shift->delete();

        return redirect()->route('shifts.index')->with('status', 'تم حذف الوردية');
    }

    public function close(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $difference = $this->shifts->closeShift(
                $shift->id,
                (float) $validated['counted_cash'],
                auth()->id(),
                $validated['notes'] ?? null
            );

            $status = $difference < 0 ? 'عجز' : ($difference > 0 ? 'زيادة' : 'مطابق');
            return redirect()->route('shifts.index')
                ->with('status', "تم إغلاق الوردية — {$status}: " . number_format(abs($difference), 2));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }
    }

    public function movements(Shift $shift): View
    {
        $shift->load(['cashierUser', 'location', 'closedBy']);

        $sales = Sale::with('cashierUser')->where('shift_id', $shift->id)->orderBy('id')->get();
        $stockMoves = StockMove::with('user')->where('shift_id', $shift->id)->orderBy('id')->get();
        $expenses = Expense::with('category')->where('shift_id', $shift->id)->orderBy('id')->get();
        $shiftExpenses = ShiftExpense::with(['user', 'approver'])->where('shift_id', $shift->id)->orderBy('id')->get();
        $cashTransfers = CashTransfer::with(['fromUser', 'toUser', 'approver'])->where('from_shift_id', $shift->id)->orderBy('id')->get();

        $cashSales = (float) $sales->sum('paid_cash');
        $cardSales = (float) $sales->sum('paid_card');
        $change = (float) $sales->sum('change');
        $expensesTotal = (float) $expenses->sum('amount');
        $shiftExpensesTotal = (float) $shiftExpenses->sum('amount');
        $transfersTotal = (float) $cashTransfers->sum('amount');
        $netCash = $cashSales - $change - $shiftExpensesTotal - $transfersTotal;
        $expected = round((float) $shift->opening_cash + $netCash, 2);

        return view('shifts.movements', compact(
            'shift', 'sales', 'stockMoves', 'expenses', 'shiftExpenses', 'cashTransfers',
            'cashSales', 'cardSales', 'change', 'expensesTotal', 'shiftExpensesTotal',
            'transfersTotal', 'expected'
        ));
    }

    public function export()
    {
        $filename = 'shifts-'.now()->format('Y-m-d').'.csv';
        $shifts = Shift::with('cashierUser')->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($shifts) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم', 'الموظف', 'الفرع', 'الحالة', 'البداية', 'النهاية', 'رصيد البداية', 'المتوقع', 'المعدود', 'الفرق']);
            foreach ($shifts as $s) {
                $diff = $s->counted_cash !== null && $s->expected_cash !== null
                    ? round((float) $s->counted_cash - (float) $s->expected_cash, 2)
                    : '';
                fputcsv($out, [
                    $s->id, $s->cashierUser?->name, $s->location_id, $s->status,
                    $s->opened_at, $s->closed_at, $s->opening_cash, $s->expected_cash, $s->counted_cash, $diff,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}