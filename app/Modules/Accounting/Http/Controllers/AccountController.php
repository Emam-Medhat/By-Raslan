<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Purchasing\Models\Receipt;
use App\Modules\Purchasing\Models\ReceiptLine;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Services\PurchasingService;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController
{
    public function __construct(private readonly PurchasingService $purchasing)
    {
    }

    public function index(): View
    {
        $rows = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $sales = (float) Sale::whereDate('at', $day)->sum('total');
            $expenses = (float) Expense::whereDate('day', $day)->where('status', 'ok')->sum('amount');
            $purchases = (float) ReceiptLine::query()
                ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
                ->whereDate('receipts.created_at', $day)
                ->sum(DB::raw('receipt_lines.accepted * receipt_lines.price'));
            $rows[] = [
                'day' => $day,
                'sales' => round($sales, 2),
                'expenses' => round($expenses, 2),
                'purchases' => round($purchases, 2),
                'net' => round($sales - $expenses - $purchases, 2),
            ];
        }

        return view('accounts.index', compact('rows'));
    }

    public function suppliers(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $balances = [];
        foreach ($suppliers as $supplier) {
            $purchases = (float) ReceiptLine::query()
                ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
                ->where('receipts.supplier_id', $supplier->id)
                ->sum(DB::raw('receipt_lines.accepted * receipt_lines.price'));
            $paid = (float) SupplierPayment::where('supplier_id', $supplier->id)->sum('amount');
            $balances[$supplier->id] = ['purchases' => $purchases, 'paid' => $paid, 'balance' => round($purchases - $paid, 2)];
        }

        return view('accounts.suppliers.index', compact('suppliers', 'balances'));
    }

    public function supplier(Supplier $supplier): View
    {
        $receipts = Receipt::where('supplier_id', $supplier->id)->with('lines')->orderByDesc('id')->get();
        $payments = SupplierPayment::where('supplier_id', $supplier->id)->orderByDesc('id')->get();
        $purchases = (float) ReceiptLine::query()
            ->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
            ->where('receipts.supplier_id', $supplier->id)
            ->sum(DB::raw('receipt_lines.accepted * receipt_lines.price'));
        $paid = (float) $payments->sum('amount');
        $balance = round($purchases - $paid, 2);

        $ledger = collect();
        foreach ($receipts as $r) {
            $amt = (float) $r->lines->sum(fn ($l) => $l->accepted * $l->price);
            $ledger->push(['at' => $r->created_at, 'ref' => 'GRN-'.$r->no, 'note' => $r->invoice_no ?? '', 'debit' => $amt, 'credit' => 0]);
        }
        foreach ($payments as $p) {
            $ledger->push(['at' => $p->at ?? $p->created_at, 'ref' => 'PAY-'.$p->no, 'note' => $p->method.($p->ref ? ' '.$p->ref : ''), 'debit' => 0, 'credit' => (float) $p->amount]);
        }
        $ledger = $ledger->sortByDesc('at')->values();

        return view('accounts.suppliers.show', compact('supplier', 'ledger', 'purchases', 'paid', 'balance'));
    }

    public function paySupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,check,instapay'],
            'ref' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->purchasing->paySupplier($request->user(), $supplier->id, (float) $data['amount'], $data['method'], $data['ref'] ?? null, $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تسجيل الدفعة');
    }
}
