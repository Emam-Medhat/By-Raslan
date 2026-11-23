@extends('layouts.app')

@section('title', 'المشتريات')
@section('page-title', 'أوامر التوريد')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">أوامر التوريد</h1>
    @can('purchase.edit')
        <a href="{{ route('purchasing.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">أمر توريد جديد</a>
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">المورد</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">البنود</th>
                <th class="text-right px-4 py-3">المتوقع</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $order->no }}</td>
                    <td class="px-4 py-3">{{ $order->supplier?->name }}</td>
                    <td class="px-4 py-3">{{ $order->status }}</td>
                    <td class="px-4 py-3">{{ $order->lines_count }}</td>
                    <td class="px-4 py-3">{{ $order->expected ?? '—' }}</td>
                    <td class="px-4 py-3"><a href="{{ route('purchasing.show', $order) }}" class="underline text-brand-700">تفاصيل</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد أوامر</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection