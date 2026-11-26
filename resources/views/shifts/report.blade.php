@extends('layouts.app')

@section('title', 'تقرير إغلاق اليومية')
@section('page-title', 'تقرير إغلاق اليومية / العهدة')

@section('content')
@php
    $s = $shift;
    $t = $totals;
@endphp
<div class="space-y-4">
    <div class="ds-card p-5 grid gap-3 sm:grid-cols-4 text-sm">
        <div><div class="text-slate-500">الفرع</div><div class="font-bold">{{ $s->location?->name ?? $s->location_id }}</div></div>
        <div><div class="text-slate-500">الموظف</div><div class="font-bold">{{ $s->cashierUser?->name }}</div></div>
        <div><div class="text-slate-500">بداية اليومية</div><div>{{ $s->opened_at?->format('Y-m-d H:i') }}</div></div>
        <div><div class="text-slate-500">نهاية اليومية</div><div>{{ $s->closed_at?->format('Y-m-d H:i') ?? 'مفتوحة' }}</div></div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ds-kpi"><div class="ds-kpi-label">عدد الأوردرات</div><div class="mt-1 font-bold num">{{ $t['ordersCount'] }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">إجمالي المبيعات</div><div class="mt-1 font-bold num">{{ number_format($t['totalSales'],2) }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">إجمالي الكاش</div><div class="mt-1 font-bold num">{{ number_format($t['cashSales'],2) }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">إجمالي إلكتروني</div><div class="mt-1 font-bold num">{{ number_format($t['electronicSales'],2) }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">عدد المرتجعات</div><div class="mt-1 font-bold num">{{ $t['returnsCount'] }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">قيمة المرتجعات</div><div class="mt-1 font-bold num">{{ number_format($t['returnsTotal'],2) }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">صافي المبيعات</div><div class="mt-1 font-bold num">{{ number_format($t['netSales'],2) }}</div></div>
        <div class="ds-kpi"><div class="ds-kpi-label">العهدة الحالية (متوقعة)</div><div class="mt-1 font-bold num">{{ number_format($t['expected_cash'],2) }}</div></div>
    </div>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-3">تفصيل طرق التحصيل</h3>
        <div class="grid gap-3 sm:grid-cols-5 text-sm">
            @foreach(['cash'=>'Cash','card'=>'Visa/Card','instapay'=>'InstaPay','wallet'=>'Wallet','mixed'=>'Mixed'] as $k=>$label)
                <div class="rounded-xl border border-slate-200 p-3"><div class="text-slate-500">{{ $label }}</div><div class="font-bold num">{{ number_format($t['by_method'][$k] ?? 0,2) }}</div></div>
            @endforeach
        </div>
    </div>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-3">العهدة</h3>
        <div class="grid gap-3 sm:grid-cols-3 text-sm">
            <div><div class="text-slate-500">العهدة السابقة / رصيد البداية</div><div class="font-bold num">{{ number_format((float)$s->opening_cash,2) }}</div></div>
            <div><div class="text-slate-500">تحصيل اليوم النقدي</div><div class="font-bold num">{{ number_format($t['cashSales'],2) }}</div></div>
            <div><div class="text-slate-500">إجمالي العهدة الحالية</div><div class="font-bold num">{{ number_format($t['expected_cash'],2) }}</div></div>
        </div>
        <p class="text-xs text-slate-500 mt-3">التحصيل الإلكتروني لا يدخل ضمن العهدة النقدية. العهدة تراكمية ولا يتم تصفيرها تلقائياً.</p>
    </div>

    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b font-bold">تفاصيل الأوردرات/الفواتير</div>
        <table class="ds-table">
            <thead><tr><th>#</th><th>الوقت</th><th>الإجمالي</th><th>طريقة التحصيل</th><th>نقدي</th><th>إلكتروني</th><th>الحالة</th></tr></thead>
            <tbody>
            @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale->no }}</td>
                    <td class="text-xs">{{ $sale->at?->format('Y-m-d H:i') }}</td>
                    <td class="num">{{ number_format((float)$sale->total,2) }}</td>
                    <td>{{ $sale->pay_method }}</td>
                    <td class="num">{{ number_format((float)$sale->paid_cash - (float)$sale->change,2) }}</td>
                    <td class="num">{{ number_format((float)$sale->paid_card,2) }}</td>
                    <td>{{ $sale->delivery_status }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
