<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Family;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Services\ItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController
{
    public function __construct(private readonly ItemService $items)
    {
    }

    public function index(): View
    {
        $items = Item::with(['family', 'barcodes'])->orderBy('name')->get();
        $families = Family::orderBy('name')->get();

        return view('items.index', compact('items', 'families'));
    }

    public function edit(Item $item): View
    {
        $families = Family::orderBy('name')->get();

        return view('items.edit', compact('item', 'families'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'family_id' => ['nullable', 'exists:families,id'],
            'kind' => ['required', 'string', 'in:'.implode(',', array_keys(Item::KINDS))],
            'unit' => ['required', 'string', 'max:20'],
            'scale_plu' => ['nullable', 'string', 'max:32'],
            'barcodes' => ['nullable', 'string', 'max:1000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $this->items->create($this->payload($request), $this->barcodes($request));

        return redirect()->route('items.index')->with('status', 'تمت إضافة الصنف');
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'family_id' => ['nullable', 'exists:families,id'],
            'kind' => ['required', 'string', 'in:'.implode(',', array_keys(Item::KINDS))],
            'unit' => ['required', 'string', 'max:20'],
            'scale_plu' => ['nullable', 'string', 'max:32'],
            'barcodes' => ['nullable', 'string', 'max:1000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $this->items->update($item, $this->payload($request), $this->barcodes($request));

        return redirect()->route('items.index')->with('status', 'تم تحديث الصنف');
    }

    public function storeFamily(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        Family::firstOrCreate(['name' => $data['name']]);

        return back()->with('status', 'تمت إضافة العائلة');
    }

    private function payload(Request $request): array
    {
        return [
            'name' => $request->input('name'),
            'family_id' => $request->input('family_id') ?: null,
            'kind' => $request->input('kind'),
            'unit' => $request->input('unit'),
            'scale_plu' => $request->input('scale_plu') ?: null,
            'active' => $request->boolean('active', true),
        ];
    }

    /** @return list<string> */
    private function barcodes(Request $request): array
    {
        $raw = (string) $request->input('barcodes', '');

        return preg_split('/[\s,،|]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}