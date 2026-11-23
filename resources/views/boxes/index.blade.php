@extends('layouts.app')

@section('title', 'تتبّع العلبة')
@section('page-title', 'تتبّع العلبة')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('boxes.index') }}" class="flex gap-2 max-w-md">
        <input name="sn" value="{{ $sn }}" placeholder="رقم العلبة" class="flex-1 rounded-lg border border-slate-300 px-3 py-2">
        <button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold">بحث</button>
    </form>

    @if ($sn && ! $box)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm">لا توجد علبة بالرقم {{ $sn }}</div>
    @endif

    @if ($box)
        <div class="bg-white rounded-xl border border-slate-200 p-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs text-slate-500">الصنف</div><div class="font-semibold">{{ $box->item?->name }}</div></div>
            <div><div class="text-xs text-slate-500">الوزن</div><div class="font-semibold">{{ $box->weight }}</div></div>
            <div><div class="text-xs text-slate-500">الموقع</div><div class="font-semibold">{{ $box->location_id }}</div></div>
            <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $box->status }}</div></div>
        </div>

        @if ($box->status === 'missing' && auth()->user()?->hasPermission('stock.adjust'))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="font-semibold text-amber-900 mb-3">العلبة متسجلة ناقصة — حسم الحالة</div>
                <form method="POST" action="{{ route('boxes.resolve-missing') }}" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <input type="hidden" name="sn" value="{{ $box->sn }}">
                    <label class="flex-1 min-w-48 text-sm">ملاحظة
                        <input name="note" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <button name="found" value="1" class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-bold">اتلقت</button>
                    <button name="found" value="0" class="bg-rose-600 text-white rounded-lg px-4 py-2 font-bold">ضاعت</button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-right px-4 py-3">التاريخ</th>
                        <th class="text-right px-4 py-3">النوع</th>
                        <th class="text-right px-4 py-3">الكمية</th>
                        <th class="text-right px-4 py-3">الموقع</th>
                        <th class="text-right px-4 py-3">السبب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($history as $move)
                        <tr>
                            <td class="px-4 py-3">{{ $move->at }}</td>
                            <td class="px-4 py-3">{{ $move->kind }}</td>
                            <td class="px-4 py-3">{{ $move->qty }}</td>
                            <td class="px-4 py-3">{{ $move->location_id }}</td>
                            <td class="px-4 py-3">{{ $move->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا توجد حركات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection