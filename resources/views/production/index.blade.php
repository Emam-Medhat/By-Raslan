@extends('layouts.app')

@section('title', 'الإنتاج')
@section('page-title', 'أوامر الإنتاج')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">أوامر الإنتاج</h1>
    <div class="flex gap-2">
        @can('items.edit')<a href="{{ route('production.routes') }}" class="bg-white border rounded-lg px-4 py-2 text-sm">مشوار الغرف</a>@endcan
        @can('prod.orders')<a href="{{ route('production.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">أمر إنتاج</a>@endcan
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">الصنف</th>
                <th class="text-right px-4 py-3">الكمية</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">الموعد</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $order->no }}</td>
                    <td class="px-4 py-3">{{ $order->item?->name }}</td>
                    <td class="px-4 py-3">{{ $order->qty }}</td>
                    <td class="px-4 py-3">{{ $order->status }}</td>
                    <td class="px-4 py-3">{{ $order->due }}</td>
                    <td class="px-4 py-3"><a href="{{ route('production.show', $order) }}" class="underline text-brand-700">فتح</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد أوامر</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection