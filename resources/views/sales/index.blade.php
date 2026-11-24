@extends('layouts.app')

@section('title', 'المبيعات')
@section('page-title', 'المبيعات والفواتير')

@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        @can('acc.view')
            <a href="{{ route('sales.export') }}" class="ds-btn ds-btn-secondary ds-btn-sm">⬇ تصدير CSV</a>
        @endcan
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>الكاشير</th>
                    <th>الإجمالي</th>
                    <th>نقدي</th>
                    <th>فيزا</th>
                    <th>الباقي</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td class="font-semibold">{{ $sale->no }}</td>
                        <td>{{ $sale->cashierUser?->name }}</td>
                        <td class="num font-bold">{{ number_format($sale->total, 2) }}</td>
                        <td class="num">{{ number_format($sale->paid_cash, 2) }}</td>
                        <td class="num">{{ number_format($sale->paid_card, 2) }}</td>
                        <td class="num">{{ number_format($sale->change, 2) }}</td>
                        <td class="num" style="font-size: 12px;">{{ $sale->at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ds-empty">لا توجد مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sales->links() }}</div>
</div>
@endsection