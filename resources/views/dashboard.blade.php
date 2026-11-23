@extends('layouts.app')

@section('title', 'الرئيسية')
@section('page-title', 'الرئيسية')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['مبيعات اليوم', $stats['sales_today'], 'bg-brand-600'],
        ['طلبات اليوم', $stats['orders_today'], 'bg-amber-500'],
        ['مصروفات اليوم', $stats['expenses_today'], 'bg-rose-500'],
        ['علب في المخزون', $stats['boxes_in_stock'], 'bg-emerald-600'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm text-slate-500">{{ $label }}</div>
            <div class="text-2xl font-extrabold mt-1">{{ $value }}</div>
            <div class="h-1 w-12 {{ $color }} rounded mt-3"></div>
        </div>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-2 mt-6">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">أحدث المبيعات</div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($recentSales as $sale)
                    <tr>
                        <td class="px-4 py-2">#{{ $sale->no }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $sale->cashierUser?->name }}</td>
                        <td class="px-4 py-2 font-semibold text-left">{{ $sale->total }} ج</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-4 text-center text-slate-400">لا توجد مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">أحدث الطلبات</div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($recentOrders as $order)
                    <tr>
                        <td class="px-4 py-2">#{{ $order->no }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $order->customer?->name }}</td>
                        <td class="px-4 py-2">{{ $order->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-4 text-center text-slate-400">لا توجد طلبات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection