@extends('layouts.app')

@section('title', 'المخزون')
@section('page-title', 'المخزون والأرصدة')

@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('stock.export') }}" class="ds-btn ds-btn-secondary ds-btn-sm">⬇ تصدير CSV</a>
    </div>

    @can('stock.adjust')
        <form method="POST" action="{{ route('stock.adjust') }}" class="ds-card p-5 space-y-3">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label class="ds-label">الصنف</label>
                    <select name="item_id" class="ds-input @error('item_id') !border-red-400 @enderror">
                        @foreach (\App\Modules\Catalog\Models\Item::orderBy('name')->get() as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('item_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="ds-label">الموقع</label>
                    <select name="location_id" class="ds-input @error('location_id') !border-red-400 @enderror">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="ds-label">الكمية <span class="text-slate-400 font-normal">(±)</span></label>
                    <input name="qty" type="number" step="0.001" value="{{ old('qty') }}" placeholder="مثال: 5 أو -2" class="ds-input @error('qty') !border-red-400 @enderror">
                    @error('qty')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="ds-label">السبب</label>
                    <input name="note" value="{{ old('note') }}" placeholder="سبب التسوية" class="ds-input @error('note') !border-red-400 @enderror">
                    @error('note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button class="ds-btn ds-btn-primary px-5">تسجيل تسوية</button>
            </div>
        </form>
    @endcan

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>الموقع</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($balances as $balance)
                    <tr>
                        <td>{{ $items[$balance['item_id']]->name ?? $balance['item_id'] }}</td>
                        <td>{{ $locations[$balance['location_id']]->name ?? $balance['location_id'] }}</td>
                        <td class="num font-bold">{{ rtrim(rtrim(number_format($balance['qty'], 3, '.', ''), '0'), '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ds-empty">لا توجد حركات مخزنية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection