<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Services\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController
{
    public function __construct(private readonly PurchasingService $purchasing)
    {
    }

    public function index(): View
    {
        $orders = PurchaseOrder::with('supplier')->withCount('lines')->orderByDesc('id')->get();

        return view('purchasing.index', compact('orders'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();

        return view('purchasing.create', compact('suppliers', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'lines.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = array_map(fn ($l) => [
            'item_id' => (int) $l['item_id'],
            'qty' => (float) $l['qty'],
            'price' => (float) ($l['price'] ?? 0),
        ], $data['lines']);

        $po = $this->purchasing->createPo(
            (int) $data['supplier_id'],
            $data['expected'] ?? null,
            $data['note'] ?? null,
            $lines,
            auth()->id(),
        );

        return redirect()->route('purchasing.show', $po)->with('status', 'تم إنشاء أمر التوريد');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['lines.item', 'supplier']);
        $order = $purchaseOrder;

        return view('purchasing.show', compact('order'));
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->purchasing->cancelPo($purchaseOrder);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['po' => $e->getMessage()]);
        }

        return back()->with('status', 'تم إلغاء أمر التوريد');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array'],
            'invoice_no' => ['nullable', 'string', 'max:120'],
            'override_id' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $lines = [];
        foreach ($data['lines'] as $lineNo => $line) {
            $lines[] = [
                'line' => (int) $lineNo,
                'weighed' => (float) ($line['weighed'] ?? 0),
                'rejected' => (float) ($line['rejected'] ?? 0),
                'price' => (float) ($line['price'] ?? 0),
                'note' => $line['note'] ?? null,
                'temp' => $line['temp'] ?? null,
                'expires' => $line['expires'] ?? null,
                'checks' => $line['checks'] ?? [],
                'docs' => $line['docs'] ?? [],
            ];
        }

        try {
            $result = $this->purchasing->receivePo($purchaseOrder->id, $lines, $data['invoice_no'] ?? null, auth()->id(), isset($data['override_id']) ? (int) $data['override_id'] : null, $data['reason'] ?? null);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_starts_with($msg, 'OVERRIDE_REQUIRED::')) {
                $payload = json_decode(substr($msg, 19), true) ?: [];
                return back()->withErrors(['receipt' => 'الاستلام فيه مخالفات وعايز تجاوز'])->with('override_violations', $payload['violations'] ?? [])->with('override_value', $payload['value'] ?? 0);
            }
            return back()->withErrors(['receipt' => $msg]);
        }

        $msg = 'تم الاستلام وتحديث المخزون';
        if (! empty($result['flags'])) { $msg .= ' — '.implode('، ', $result['flags']); }
        return back()->with('status', $msg);
    }

    public function receipts(): View
    {
        $receipts = \App\Modules\Purchasing\Models\Receipt::with('supplier')->orderByDesc('id')->paginate(20);

        return view('purchasing.receipts.index', compact('receipts'));
    }

    public function receiptShow(\App\Modules\Purchasing\Models\Receipt $receipt): View
    {
        $receipt->load(['supplier', 'lines.item', 'purchaseOrder.supplier']);

        return view('purchasing.receipts.show', compact('receipt'));
    }

    public function receivePage(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['lines.item', 'supplier']);
        $order = $purchaseOrder;
        $specs = \App\Modules\Purchasing\Models\ItemSpecVersion::query()
            ->whereIn('item_id', $order->lines->pluck('item_id'))
            ->orderByDesc('version')
            ->get()
            ->groupBy('item_id')
            ->map(fn ($g) => $g->first());
        $approved = \App\Modules\Purchasing\Models\OverrideRequest::where('kind', 'recv')
            ->where('ref', 'PO-'.$order->no)->where('status', 'approved')->orderByDesc('id')->get();

        return view('purchasing.receive', compact('order', 'specs', 'approved'));
    }

    public function requestOverride(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'violations' => ['required', 'array'],
            'violations.*' => ['string'],
            'value' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        \App\Modules\Purchasing\Models\OverrideRequest::create([
            'kind' => 'recv',
            'ref' => 'PO-'.$purchaseOrder->no,
            'violations' => $data['violations'],
            'value' => $data['value'],
            'reason' => trim($data['reason']),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
        return back()->with('status', 'اتطلب التجاوز ومستني موافقة');
    }
}