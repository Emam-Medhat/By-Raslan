<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Modules\Purchasing\Models\ReceiptLine;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController
{
    public function __construct(private readonly PurchasingService $purchasing)
    {
    }

    public function index(): View
    {
        $suppliers = Supplier::orderBy('name')->get();

        $balances = [];
        foreach ($suppliers as $supplier) {
            $purchases = (float) ReceiptLine::query()
                ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
                ->where('receipts.supplier_id', $supplier->id)
                ->sum(DB::raw('receipt_lines.accepted * receipt_lines.price'));

            $paid = (float) SupplierPayment::where('supplier_id', $supplier->id)->sum('amount');

            $balances[$supplier->id] = [
                'purchases' => $purchases,
                'paid' => $paid,
                'balance' => round($purchases - $paid, 2),
            ];
        }

        return view('purchasing.suppliers.index', compact('suppliers', 'balances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'terms' => ['nullable', 'string', 'max:120'],
        ]);

        Supplier::create($data + ['active' => true]);

        return back()->with('status', 'تمت إضافة المورد');
    }

    public function toggle(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['active' => ! $supplier->active]);

        return back()->with('status', $supplier->active ? 'تم التفعيل' : 'تم التعطيل');
    }

    public function pay(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,check,instapay'],
            'ref' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->purchasing->paySupplier(
                $request->user(),
                $supplier->id,
                (float) $data['amount'],
                $data['method'],
                $data['ref'] ?? null,
                $data['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تسجيل الدفعة');
    }
}