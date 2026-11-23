<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Purchasing\Models\ItemSpecVersion;
use App\Modules\Purchasing\Services\SpecService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecController
{
    public function __construct(private readonly SpecService $specs)
    {
    }

    public function index(): View
    {
        $items = Item::orderBy('name')->get();
        $versions = ItemSpecVersion::with('item')->orderByDesc('id')->take(50)->get();

        return view('purchasing.specs.index', compact('items', 'versions'));
    }

    public function show(Item $item): View
    {
        $versions = ItemSpecVersion::where('item_id', $item->id)->orderByDesc('version')->get();
        $current = $versions->first();

        return view('purchasing.specs.show', compact('item', 'versions', 'current'));
    }

    public function save(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'checks' => ['nullable', 'array'],
            'checks.*' => ['string'],
            'certs' => ['nullable', 'array'],
            'certs.*' => ['string'],
            'temp_max' => ['nullable', 'numeric'],
            'min_shelf_days' => ['nullable', 'integer'],
        ]);

        $snapshot = array_filter([
            'checks' => array_map(fn ($t) => ['t' => $t], $data['checks'] ?? []),
            'certs' => array_map(fn ($n) => ['n' => $n], $data['certs'] ?? []),
            'temp_max' => $data['temp_max'] ?? null,
            'min_shelf_days' => $data['min_shelf_days'] ?? null,
        ], fn ($v) => $v !== null && $v !== []);

        $this->specs->saveVersion($item->id, $snapshot, $request->user()->id);

        return back()->with('status', 'تم حفظ نسخة المواصفة واعتمادها');
    }
}