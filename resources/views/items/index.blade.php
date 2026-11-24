@extends('layouts.app')

@section('title', 'الأصناف')
@section('page-title', 'الأصناف والعائلات')

@section('content')
<div class="space-y-4" x-data="{ tab: 'items', familiesOpen: {{ $errors->any() && old('_form') === 'family_create' ? 'true' : 'false' }}, createOpen: {{ $errors->any() && old('_form') === 'item_create' ? 'true' : 'false' }} }">
    <div class="flex flex-wrap items-center gap-2">
        <button @click="tab='items'" :class="tab==='items' ? 'ds-btn ds-btn-primary' : 'ds-btn ds-btn-secondary'" class="px-4 py-2 text-sm font-bold">الأصناف</button>
        @can('items.edit')
            <button @click="familiesOpen = true" class="ds-btn ds-btn-secondary px-4 py-2 text-sm font-bold">
                <x-icon name="layers" size="16" class="ml-1" /> العائلات
            </button>
            <div class="flex-1"></div>
            <button @click="createOpen = true" class="ds-btn ds-btn-accent px-4 py-2 text-sm font-bold">
                <x-icon name="plus" size="16" class="ml-1" /> إضافة صنف
            </button>
        @endcan
    </div>

    @can('items.edit')
    {{-- Families modal --}}
    <div x-show="familiesOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="familiesOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @keydown.escape.window="familiesOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">العائلات</h2>
                    <p class="text-xs text-slate-400 mt-0.5">أضف عائلة جديدة لتنظيم الأصناف</p>
                </div>
                <button @click="familiesOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <div class="p-5 space-y-4">
                <form method="POST" action="{{ route('items.families.store') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="_form" value="family_create">
                    <input name="name" value="{{ old('_form') === 'family_create' ? old('name') : '' }}" placeholder="اسم العائلة" class="flex-1 ds-input" required>
                    <button class="ds-btn ds-btn-accent px-4 py-2 font-bold">إضافة</button>
                </form>
                @if($errors->any() && old('_form') === 'family_create')
                    <p class="text-xs text-red-600 font-semibold">{{ $errors->first() }}</p>
                @endif
                <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200">
                    <table class="ds-table">
                        <thead><tr><th>العائلة</th><th class="text-left">عدد الأصناف</th></tr></thead>
                        <tbody>
                            @forelse($families as $family)
                                <tr>
                                    <td class="font-semibold text-primary-800">{{ $family->name }}</td>
                                    <td class="text-left"><span class="ds-badge ds-badge-neutral">{{ $family->items_count ?? $family->items()->count() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="ds-empty">لا توجد عائلات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Create item modal --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="createOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">إضافة صنف جديد</h2>
                    <p class="text-xs text-slate-400 mt-0.5">أدخل بيانات الصنف الأساسية والباركود والـ PLU</p>
                </div>
                <button @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <form method="POST" action="{{ route('items.store') }}" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="item_create">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="ds-label">اسم الصنف <span class="text-red-500">*</span></label>
                        <input name="name" value="{{ old('_form') === 'item_create' ? old('name') : '' }}" class="ds-input @error('name') !border-red-400 @enderror" required>
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">العائلة</label>
                        <select name="family_id" class="ds-input @error('family_id') !border-red-400 @enderror">
                            <option value="">— بدون عائلة —</option>
                            @foreach ($families as $family)
                                <option value="{{ $family->id }}" @selected(old('_form') === 'item_create' && old('family_id') == $family->id)>{{ $family->name }}</option>
                            @endforeach
                        </select>
                        @error('family_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">النوع <span class="text-red-500">*</span></label>
                        <select name="kind" class="ds-input @error('kind') !border-red-400 @enderror">
                            @foreach (\App\Modules\Catalog\Models\Item::KINDS as $value => $label)
                                <option value="{{ $value }}" @selected(old('_form') === 'item_create' ? old('kind', 'product') === $value : $value === 'product')>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('kind') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">الوحدة <span class="text-red-500">*</span></label>
                        <input name="unit" value="{{ old('_form') === 'item_create' ? old('unit', 'كرتونة') : 'كرتونة' }}" class="ds-input @error('unit') !border-red-400 @enderror" required>
                        @error('unit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">PLU الميزان</label>
                        <input name="scale_plu" value="{{ old('_form') === 'item_create' ? old('scale_plu') : '' }}" class="ds-input @error('scale_plu') !border-red-400 @enderror" dir="ltr" placeholder="مثال: 101">
                        @error('scale_plu') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="ds-label">الباركود</label>
                        <input name="barcodes" value="{{ old('_form') === 'item_create' ? old('barcodes') : '' }}" class="ds-input @error('barcodes') !border-red-400 @enderror" dir="ltr" placeholder="افصل بين الأكواد بفاصلة">
                        @error('barcodes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" value="1" @checked(old('_form') === 'item_create' ? old('active', '1') == '1' : true) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500/30"> صنف مفعّل
                    </label>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary px-4 py-2">إلغاء</button>
                    <button class="ds-btn ds-btn-primary px-5 py-2 font-bold"><x-icon name="plus" size="16" class="ml-1" /> حفظ الصنف</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- Items --}}
    <div x-show="tab==='items'">
        <form method="GET" action="{{ route('items.index') }}" class="ds-card p-4 mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <x-icon name="search" size="16" />
                </span>
                <input name="q" value="{{ $q ?? '' }}" placeholder="بحث باسم الصنف أو الباركود أو PLU الميزان..." class="ds-input pr-10">
            </div>
            <div class="flex items-center gap-2">
                <button class="ds-btn ds-btn-primary px-4 py-2">بحث</button>
                @if(!empty($q))
                    <a href="{{ route('items.index') }}" class="ds-btn ds-btn-secondary px-4 py-2">مسح</a>
                @endif
            </div>
        </form>

        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th>العائلة</th>
                        <th>النوع</th>
                        <th>الوحدة</th>
                        <th>الباركود</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="font-semibold">
                                {{ $item->name }}
                                @can('items.edit')<a href="{{ route('items.edit', $item) }}" class="ds-link text-xs mr-2">تعديل</a>@endcan
                            </td>
                            <td>{{ $item->family?->name ?? '—' }}</td>
                            <td>{{ \App\Modules\Catalog\Models\Item::KINDS[$item->kind] ?? $item->kind }}</td>
                            <td>{{ $item->unit }}</td>
                            <td dir="ltr">{{ $item->barcodes->pluck('code')->implode(', ') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ds-empty">لا توجد أصناف</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
