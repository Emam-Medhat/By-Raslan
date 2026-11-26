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

        $totals = $this->shifts->shiftTotals($shift);
        $cashSales = $totals['cashSales'];
        $cardSales = $totals['electronicSales'];
        $change = (float) $sales->sum('change');
        $shiftExpensesTotal = $totals['shift_expenses_total'];
        $expensesTotal = (float) $expenses->sum('amount');
        $transfersTotal = $totals['transfers_out'];
        $expected = $totals['expected_cash'];

        return view('shifts.show', compact(
            'shift', 'sales', 'shiftExpenses', 'cashTransfers', 'stockMoves', 'expenses',
            'cashSales', 'cardSales', 'change', 'shiftExpensesTotal', 'expensesTotal',
            'transfersTotal', 'expected', 'totals'
        ));
    }

    public function report(Shift $shift): View
    {
        $shift->load(['cashierUser','location','closedBy']);
        $sales = Sale::with('cashierUser')->where('shift_id', $shift->id)->orderBy('id')->get();
        $totals = $this->shifts->shiftTotals($shift);
        return view('shifts.report', compact('shift','sales','totals'));
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
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->shifts->closeShift(
                $shift->id,
                null,
                auth()->id(),
                $validated['notes'] ?? null
            );

            return redirect()->route('shifts.report', $shift->fresh()->id)
                ->with('status', 'تم إغلاق اليومية وحساب العهدة تلقائيًا.');
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
        // Same single expected-cash formula as the close path.
        $expected = $this->shifts->calculateExpectedCash($shift);

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
            fputcsv($out, ['رقم', 'الموظف', 'الفرع', 'الحالة', 'البداية', 'النهاية', 'رصيد البداية', 'العهدة عند الإغلاق']);
            foreach ($shifts as $s) {
                fputcsv($out, [
                    $s->id, $s->cashierUser?->name, $s->location_id, $s->status,
                    $s->opened_at, $s->closed_at, $s->opening_cash, $s->expected_cash,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
