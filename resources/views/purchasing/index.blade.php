@extends('layouts.app')

@section('title', 'المشتريات')
@section('page-title', 'أوامر التوريد')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">أوامر التوريد</h1>
        @can('purchase.edit')
            <a href="{{ route('purchasing.create') }}" class="ds-btn ds-btn-primary">أمر توريد جديد</a>
        @endcan
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>المورد</th>
                    <th>الحالة</th>
                    <th>البنود</th>
                    <th>المتوقع</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-semibold">{{ $order->no }}</td>
                        <td>{{ $order->supplier?->name }}</td>
                        <td><span class="ds-badge ds-badge-neutral">{{ $order->status }}</span></td>
                        <td class="num">{{ $order->lines_count }}</td>
                        <td class="num">{{ $order->expected ?? '—' }}</td>
                        <td><a href="{{ route('purchasing.show', $order) }}" class="ds-link">تفاصيل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد أوامر</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection