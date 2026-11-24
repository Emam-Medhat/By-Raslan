@extends('layouts.app')

@section('title', 'الإنتاج')
@section('page-title', 'أوامر الإنتاج')

@section('content')
<div class="space-y-4" x-data="{ createOpen: {{ ($errors->any() || request()->boolean('create')) ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold">أوامر الإنتاج</h1>
        <div class="flex gap-2">
            @can('items.edit')<a href="{{ route('production.routes') }}" class="ds-btn ds-btn-secondary">مشوار الغرف</a>@endcan
            @can('prod.orders')<button type="button" @click="createOpen = true" class="ds-btn ds-btn-primary">+ أمر إنتاج</button>@endcan
        </div>
    </div>

    @can('prod.orders')
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="createOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">أمر إنتاج جديد</h2>
                    <p class="text-xs text-slate-400 mt-0.5">أنشئ أمر إنتاج لصنف له مسار غرف محدد</p>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <form method="POST" action="{{ route('production.store') }}" class="p-5 space-y-4">
                @csrf
                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pr-5 space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div>
                    <label class="ds-label">الصنف <span class="text-red-500">*</span></label>
                    <select name="item_id" class="w-full ds-input @error('item_id') !border-red-400 @enderror" required>
                        <option value="">اختر الصنف</option>
                        @foreach(($items ?? []) as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('item_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ds-label">الكمية <span class="text-red-500">*</span></label>
                        <input name="qty" type="number" step="0.001" min="0.001" value="{{ old('qty') }}" class="w-full ds-input @error('qty') !border-red-400 @enderror" placeholder="0.000" required>
                        @error('qty')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="ds-label">تاريخ الاستحقاق</label>
                        <input name="due" type="date" value="{{ old('due') }}" class="w-full ds-input @error('due') !border-red-400 @enderror">
                        @error('due')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="ds-label">ملاحظة</label>
                    <input name="note" value="{{ old('note') }}" placeholder="ملاحظة اختيارية" class="w-full ds-input @error('note') !border-red-400 @enderror">
                    @error('note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary px-4">إلغاء</button>
                    <button class="ds-btn ds-btn-primary px-5">إنشاء أمر الإنتاج</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>الحالة</th>
                    <th>الموعد</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-semibold">{{ $order->no }}</td>
                        <td>{{ $order->item?->name }}</td>
                        <td class="num">{{ $order->qty }}</td>
                        <td><span class="ds-badge ds-badge-neutral">{{ $order->status }}</span></td>
                        <td class="num" style="font-size: 12px;">{{ $order->due }}</td>
                        <td><a href="{{ route('production.show', $order) }}" class="ds-link">فتح</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد أوامر</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection