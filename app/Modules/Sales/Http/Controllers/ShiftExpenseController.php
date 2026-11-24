<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftExpense;
use App\Modules\Sales\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftExpenseController
{
    public function __construct(private readonly ShiftService $shifts)
    {
    }

    public function index(Request $request): View
    {
        $query = ShiftExpense::with(['shift', 'user', 'approver'])->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $expenses = $query->paginate(20)->withQueryString();

        return view('shift-expenses.index', compact('expenses'));
    }

    public function create(Shift $shift): View
    {
        return view('shift-expenses.create', compact('shift'));
    }

    public function store(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->shifts->addExpense($shift, $validated);

        return redirect()->route('shifts.show', $shift)->with('status', 'تم تسجيل المصروف');
    }

    public function approve(ShiftExpense $expense): RedirectResponse
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'تم اعتماد المصروف');
    }

    public function reject(ShiftExpense $expense): RedirectResponse
    {
        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'تم رفض المصروف');
    }
}