<?php

namespace App\Modules\Returns\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Returns\Models\PurchaseReturn;
use App\Modules\Returns\Services\PurchaseReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseReturnController
{
    public function __construct(private readonly PurchaseReturnService $returns)
    {
    }

    public function index(): View
    {
        $returns = PurchaseReturn::with('supplier')->orderByDesc('id')->paginate(20);

        return view('returns.purchase', compact('returns'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();

        return view('returns.purchase-create', compact('suppliers', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'lines.*.cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->returns->request((int) $data['supplier_id'], $data['lines'], $data['reason'] ?? null, $request->user()->id);

        return redirect()->route('purchase-returns.index')->with('status', 'تم تسجيل مرتجع المورد');
    }

    public function approve(Request $request, PurchaseReturn $return): RedirectResponse
    {
        try {
            $this->returns->approve($return, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return back()->with('status', 'تمت الموافقة وخصم المخزون');
    }
}