<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Accounting\Models\ExpenseCategory;
use App\Modules\Accounting\Services\ExpenseService;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController
{
    public function __construct(private readonly ExpenseService $expenses)
    {
    }

    public function index(): View
    {
        $expenses = Expense::with('category')->orderByDesc('id')->paginate(20);
        $categories = ExpenseCategory::where('active', true)->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('accounts.expenses.index', compact('expenses', 'categories', 'locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_from' => ['required', 'in:cash,bank,shift'],
            'note' => ['nullable', 'string', 'max:255'],
            'day' => ['nullable', 'date'],
        ]);

        try {
            $this->expenses->add($request->user(), $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['expense' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تسجيل المصروف');
    }

    public function decide(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->expenses->decide($request->user(), $expense, $request->boolean('approve'), $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['expense' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تحديث المصروف');
    }
}