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

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $items = Item::query()
            ->with(['family', 'barcodes'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('scale_plu', 'like', "%{$q}%")
                        ->orWhereHas('barcodes', fn ($b) => $b->where('code', 'like', "%{$q}%"));
                });
            })
            ->orderBy('name')
            ->get();

        $families = Family::withCount('items')->orderBy('name')->get();

        return view('items.index', compact('items', 'families', 'q'));
    }

    public function edit(Item $item): View
    {
        $families = Family::orderBy('name')->get();

        return view('items.edit', compact('item', 'families'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(), $this->messages(), $this->attributes());

        $this->items->create($this->payload($request), $this->barcodes($request));

        return redirect()->route('items.index')->with('status', 'تمت إضافة الصنف');
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $request->validate($this->rules(), $this->messages(), $this->attributes());

        $this->items->update($item, $this->payload($request), $this->barcodes($request));

        return redirect()->route('items.index')->with('status', 'تم تحديث الصنف');
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'family_id' => ['nullable', 'exists:families,id'],
            'kind' => ['required', 'string', 'in:'.implode(',', array_keys(Item::KINDS))],
            'unit' => ['required', 'string', 'max:20'],
            'scale_plu' => ['nullable', 'string', 'regex:/^\d{1,6}$/'],
            'barcodes' => ['nullable', 'string', 'max:1000'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'اكتب اسم الصنف.',
            'name.max' => 'اسم الصنف أطول من المسموح (150 حرف).',
            'family_id.exists' => 'العائلة المختارة غير موجودة.',
            'kind.required' => 'اختر نوع الصنف.',
            'kind.in' => 'نوع الصنف غير صحيح.',
            'unit.required' => 'اكتب وحدة البيع (مثال: كرتونة).',
            'unit.max' => 'الوحدة أطول من المسموح (20 حرف).',
            'scale_plu.regex' => 'كود الميزان لازم يكون من رقم إلى 6 أرقام، من غير حروف أو رموز.',
            'barcodes.max' => 'الباركود طويل جداً (الحد 1000 حرف).',
            'active.boolean' => 'قيمة «مفعّل» غير صحيحة.',
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'name' => 'اسم الصنف',
            'family_id' => 'العائلة',
            'kind' => 'النوع',
            'unit' => 'الوحدة',
            'scale_plu' => 'PLU الميزان',
            'barcodes' => 'الباركود',
            'active' => 'مفعّل',
        ];
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
