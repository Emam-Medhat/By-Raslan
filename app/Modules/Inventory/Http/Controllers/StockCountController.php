<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockCountItem;
use App\Modules\Inventory\Services\StockCountService;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockCountController
{
    public function __construct(private readonly StockCountService $stockCount)
    {
    }

    public function index(Request $request): View
    {
        $query = StockCount::with(['location', 'user', 'approver'])->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($location = $request->input('location')) {
            $query->where('location_id', $location);
        }

        $counts = $query->paginate(20)->withQueryString();
        $locations = Location::orderBy('name')->get();
        $createLocations = Location::whereIn('type', ['dark', 'shop', 'warehouse'])->where('active', true)->orderBy('name')->get();

        return view('stock-counts.index', compact('counts', 'locations', 'createLocations'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('stock-counts.index', ['create' => 1]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = $this->stockCount->create($validated['location_id'], auth()->id());

        return redirect()->route('stock-counts.show', $count)->with('status', 'تم إنشاء الجرد');
    }

    public function show(StockCount $stockCount): View
    {
        $stockCount->load(['location', 'user', 'approver', 'items.item']);
        $availableItems = $this->stockCount->getLocationItems($stockCount->location_id);

        return view('stock-counts.show', compact('stockCount', 'availableItems'));
    }

    public function addItem(Request $request, StockCount $stockCount): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'actual_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $this->stockCount->addItem($stockCount, (int) $validated['item_id'], (float) $validated['actual_quantity']);

        return back()->with('status', 'تم إضافة الصنف للجرد');
    }

    public function updateItem(Request $request, StockCountItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'actual_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $this->stockCount->updateItem($item, (float) $validated['actual_quantity']);

        return back()->with('status', 'تم تحديث الكمية');
    }

    public function approve(StockCount $stockCount): RedirectResponse
    {
        if ($stockCount->status === 'approved') {
            return back()->withErrors(['count' => 'الجرد معتمد بالفعل']);
        }

        $this->stockCount->approve($stockCount, auth()->id());

        return back()->with('status', 'تم اعتماد الجرد وتسجيل التسويات');
    }
}