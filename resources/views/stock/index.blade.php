@extends('layouts.app')

@section('title', 'المخزون')
@section('page-title', 'المخزون والأرصدة')

@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('stock.export') }}" class="text-sm bg-white border border-slate-300 rounded-lg px-3 py-2 hover:bg-slate-50">⬇ تصدير CSV</a>
    </div>
    @can('stock.adjust')
        <form method="POST" action="{{ route('stock.adjust') }}" class="bg-white rounded-xl border border-slate-200 p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <select name="item_id" class="rounded-lg border border-slate-300 px-3 py-2 lg:col-span-2">
                @foreach (\App\Modules\Catalog\Models\Item::orderBy('name')->get() as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
            <select name="location_id" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <input name="qty" type="number" step="0.001" placeholder="± الكمية" class="rounded-lg border border-slate-300 px-3 py-2">
            <input name="note" placeholder="السبب" class="rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-amber-600 text-white rounded-lg px-4 py-2 font-bold">تسوية</button>
        </form>
    @endcan

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الصنف</th>
                    <th class="text-right px-4 py-3">الموقع</th>
                    <th class="text-right px-4 py-3">الرصيد</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($balances as $balance)
                    <tr>
                        <td class="px-4 py-3">{{ $items[$balance['item_id']]->name ?? $balance['item_id'] }}</td>
                        <td class="px-4 py-3">{{ $locations[$balance['location_id']]->name ?? $balance['location_id'] }}</td>
                        <td class="px-4 py-3 font-semibold">{{ rtrim(rtrim(number_format($balance['qty'], 3, '.', ''), '0'), '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">لا توجد حركات مخزنية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection