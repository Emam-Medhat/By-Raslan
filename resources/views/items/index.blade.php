@extends('layouts.app')

@section('title', 'الأصناف')
@section('page-title', 'الأصناف والعائلات')

@section('content')
<div class="space-y-4" x-data="{ tab: 'items' }">
    <div class="flex items-center gap-2">
        <button @click="tab='items'" :class="tab==='items' ? 'bg-brand-600 text-white' : 'bg-white'" class="px-4 py-2 rounded-lg text-sm font-bold border">الأصناف</button>
        @can('items.edit')
            <button @click="tab='families'" :class="tab==='families' ? 'bg-brand-600 text-white' : 'bg-white'" class="px-4 py-2 rounded-lg text-sm font-bold border">العائلات</button>
        @endcan
    </div>

    {{-- Families --}}
    <div x-show="tab==='families'" x-cloak class="bg-white rounded-xl border border-slate-200 p-5 max-w-lg">
        <form method="POST" action="{{ route('items.families.store') }}" class="flex gap-2">
            @csrf
            <input name="name" placeholder="اسم العائلة" class="flex-1 rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-bold">إضافة</button>
        </form>
    </div>

    {{-- Items --}}
    <div x-show="tab==='items'">
        @can('items.edit')
            <form method="POST" action="{{ route('items.store') }}" class="bg-white rounded-xl border border-slate-200 p-5 mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input name="name" placeholder="اسم الصنف" class="rounded-lg border border-slate-300 px-3 py-2">
                <select name="family_id" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">— عائلة —</option>
                    @foreach ($families as $family)
                        <option value="{{ $family->id }}">{{ $family->name }}</option>
                    @endforeach
                </select>
                <select name="kind" class="rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (\App\Modules\Catalog\Models\Item::KINDS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input name="unit" value="كرتونة" class="rounded-lg border border-slate-300 px-3 py-2">
                <input name="scale_plu" placeholder="PLU ميزان" class="rounded-lg border border-slate-300 px-3 py-2">
                <input name="barcodes" placeholder="باركود (افصل بفاصلة)" class="rounded-lg border border-slate-300 px-3 py-2 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" checked class="rounded"> مفعّل</label>
                <button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold">إضافة صنف</button>
            </form>
        @endcan

        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-right px-4 py-3">الصنف</th>
                        <th class="text-right px-4 py-3">العائلة</th>
                        <th class="text-right px-4 py-3">النوع</th>
                        <th class="text-right px-4 py-3">الوحدة</th>
                        <th class="text-right px-4 py-3">الباركود</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-3 font-semibold">
                            {{ $item->name }}
                            @can('items.edit')<a href="{{ route('items.edit', $item) }}" class="text-xs text-brand-700 underline mr-2">تعديل</a>@endcan
                        </td>
                            <td class="px-4 py-3">{{ $item->family?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ \App\Modules\Catalog\Models\Item::KINDS[$item->kind] ?? $item->kind }}</td>
                            <td class="px-4 py-3">{{ $item->unit }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $item->barcodes->pluck('code')->implode(', ') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا توجد أصناف</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection