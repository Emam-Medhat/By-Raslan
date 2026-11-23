<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Price;
use App\Modules\Catalog\Services\PriceService;
use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceController
{
    public function __construct(private readonly PriceService $prices)
    {
    }

    public function index(): View
    {
        $items = Item::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $prices = Price::with('item')->orderByDesc('id')->take(50)->get();

        return view('prices.index', compact('items', 'locations', 'prices'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'channel' => ['required', 'in:direct,app'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->prices->save(
            (int) $data['item_id'],
            $data['location_id'],
            $data['channel'],
            $data['price'],
            auth()->id(),
        );

        return back()->with('status', 'تم حفظ السعر');
    }
}