@extends('layouts.app')

@section('title', $stockCount->count_number)
@section('page-title', 'جرد: ' . $stockCount->count_number)

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">جرد: {{ $stockCount->count_number }}</h1>
            <p class="text-sm text-slate-500">
                {{ $stockCount->location?->name }}
                · <span class="inline-block rounded px-2 py-0.5 text-xs {{ $stockCount->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($stockCount->status === 'completed' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700') }}">{{ $stockCount->getStatusLabel() }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('stock-counts.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
            @if($stockCount->status !== 'approved')
                <button onclick="document.getElementById('approveModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-bold">اعتماد الجرد</button>
            @endif
        </div>
    </div>

    {{-- Add Item Form --}}
    @if($stockCount->status !== 'approved')
    <div x-data="{ show: false }" class="bg-white rounded-xl border border-slate-200 p-5">
        <button @click="show = !show" class="bg-accent-500 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">
            + إضافة صنف
        </button>

        <div x-show="show" x-cloak class="mt-4">
            <form method="POST" action="{{ route('stock-counts.add-item', $stockCount) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <select name="item_id" class="rounded-lg border border-slate-300 px-3 py-2" required>
                    <option value="">اختر الصنف</option>
                    @foreach($availableItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} (متوفر: {{ number_format($stockCount->items->where('item_id', $item->id)->first()?->system_quantity ?? \App\Modules\Inventory\Services\StockCountService::getStockQuantity($item->id, $stockCount->location_id), 3) }})</option>
                    @endforeach
                </select>
                <input name="actual_quantity" type="number" step="0.001" min="0" placeholder="الكمية الفعلية" class="rounded-lg border border-slate-300 px-3 py-2" required>
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-bold">إضافة</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Items Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-3 font-semibold">الصنف</th>
                    <th class="text-right px-3 py-3 font-semibold">الكمية الدفترية</th>
                    <th class="text-right px-3 py-3 font-semibold">الكمية الفعلية</th>
                    <th class="text-right px-3 py-3 font-semibold">الفرق</th>
                    <th class="text-right px-3 py-3 font-semibold">التكلفة</th>
                    <th class="text-right px-3 py-3 font-semibold">التكلفة الإجمالية</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stockCount->items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-3">{{ $item->item?->name }}</td>
                        <td class="px-3 py-3">{{ number_format((float) $item->system_quantity, 3) }}</td>
                        <td class="px-3 py-3">
                            @if($stockCount->status !== 'approved')
                                <form method="POST" action="{{ route('stock-count-items.update', $item) }}" class="flex gap-1 items-center">
                                    @csrf
                                    @method('PUT')
                                    <input name="actual_quantity" type="number" step="0.001" min="0" value="{{ $item->actual_quantity }}" class="w-24 rounded border border-slate-300 px-2 py-1">
                                    <button class="text-xs text-accent-700 hover:underline">تحديث</button>
                                </form>
                            @else
                                {{ number_format((float) $item->actual_quantity, 3) }}
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <span class="text-xs rounded px-2 py-0.5 {{ $item->difference < 0 ? 'bg-rose-100 text-rose-700' : ($item->difference > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ number_format((float) $item->difference, 3) }}
                            </span>
                        </td>
                        <td class="px-3 py-3">{{ number_format((float) $item->unit_cost, 2) }}</td>
                        <td class="px-3 py-3 font-semibold">{{ number_format((float) $item->total_cost, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">لا توجد أصناف في الجرد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Approve Modal --}}
<div id="approveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="bg-emerald-600 text-white px-5 py-4 flex items-center gap-3">
            <span class="text-xl">✓</span>
            <h3 class="font-bold">اعتماد الجرد</h3>
        </div>
        <form method="POST" action="{{ route('stock-counts.approve', $stockCount) }}" class="p-5 space-y-4">
            @csrf
            <p class="text-sm text-slate-500">سيتم اعتماد الجرد وتسجيل الفروقات كتسويات مخزنية. هل أنت متأكد؟</p>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-2 font-bold">تأكيد الاعتماد</button>
                <button type="button" onclick="document.getElementById('approveModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-slate-300">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection