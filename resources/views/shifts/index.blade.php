@extends('layouts.app')

@section('title', 'الورديات')
@section('page-title', 'تقرير الورديات')

@section('content')
@php
    $f = $filters ?? [];
    $s = $report['summary'] ?? [];
    $rows = $report['rows'] ?? collect();
@endphp
<div class="space-y-4">
    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-sm">من<input type="date" name="from" value="{{ $f['from'] ?? now()->subDays(7)->toDateString() }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="text-sm">إلى<input type="date" name="to" value="{{ $f['to'] ?? now()->toDateString() }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="text-sm">الفرع<select name="location" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">الكل</option>@foreach($locations as $loc)<option value="{{ $loc->id }}" @selected(($f['location'] ?? '')===$loc->id)>{{ $loc->name }}</option>@endforeach</select></label>
        <label class="text-sm">الحالة<select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">@foreach(['all'=>'الكل','open'=>'مفتوحة','closed'=>'مقفولة'] as $k=>$v)<option value="{{ $k }}" @selected(($f['status'] ?? 'all')===$k)>{{ $v }}</option>@endforeach</select></label>
        <label class="text-sm">المطابقة<select name="reconcile" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">@foreach(['all'=>'الكل','short'=>'عجز','over'=>'زيادة','match'=>'مطابق'] as $k=>$v)<option value="{{ $k }}" @selected(($f['reconcile'] ?? 'all')===$k)>{{ $v }}</option>@endforeach</select></label>
        <label class="text-sm">بحث<input name="search" value="{{ $f['search'] ?? '' }}" placeholder="رقم وردية أو كاشير" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <div class="sm:col-span-2 lg:col-span-6"><button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold">عرض التقرير</button></div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['الورديات',$s['shift_count']??0],['المبيعات',$s['sales_total']??0],['النقد الصافي',$s['cash_net']??0],['الفروقات',$s['mismatch_count']??0]] as $card)
            <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-sm text-slate-500">{{ $card[0] }}</div><div class="text-2xl font-extrabold mt-1">{{ is_numeric($card[1]) ? number_format((float)$card[1],2) : $card[1] }}</div></div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">#</th>
                    <th class="text-right px-4 py-3">الكاشير</th>
                    <th class="text-right px-4 py-3">الفرع</th>
                    <th class="text-right px-4 py-3">فتح</th>
                    <th class="text-right px-4 py-3">مبيعات</th>
                    <th class="text-right px-4 py-3">نقد</th>
                    <th class="text-right px-4 py-3">فيزا</th>
                    <th class="text-right px-4 py-3">مصروفات وردية</th>
                    <th class="text-right px-4 py-3">متوقع</th>
                    <th class="text-right px-4 py-3">معدود</th>
                    <th class="text-right px-4 py-3">فرق</th>
                    <th class="text-right px-4 py-3">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $r)
                    <tr>
                        <td class="px-4 py-3">{{ $r->id }}</td>
                        <td class="px-4 py-3">{{ $r->cashier_name }}</td>
                        <td class="px-4 py-3">{{ $r->location_name ?? $r->location_id }}</td>
                        <td class="px-4 py-3">{{ $r->opened_at }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$r->sales_total,2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$r->cash_net,2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$r->card_total,2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$r->shift_expenses,2) }}</td>
                        <td class="px-4 py-3">{{ $r->expected_cash }}</td>
                        <td class="px-4 py-3">{{ $r->counted_cash }}</td>
                        <td class="px-4 py-3 {{ (float)($r->difference ?? 0) < 0 ? 'text-rose-600' : ((float)($r->difference ?? 0) > 0 ? 'text-amber-600' : '') }}">{{ $r->difference }}</td>
                        <td class="px-4 py-3">{{ $r->status === 'open' ? 'مفتوحة' : 'مقفولة' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-6 text-center text-slate-400">لا توجد ورديات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
