@extends('layouts.app')

@section('title', 'المرتجعات')
@section('page-title', 'مرتجعات المبيعات')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">المرتجعات</h1>
        @can('pos.shift')<a href="{{ route('returns.create') }}" class="ds-btn ds-btn-primary">مرتجع جديد</a>@endcan
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>الفاتورة</th>
                    <th>الإجمالي</th>
                    <th>الوجهة</th>
                    <th>الاسترداد</th>
                    <th>الحالة</th>
                    <th>السبب</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td class="font-semibold">{{ $return->no }}</td>
                        <td>{{ $return->sale?->no }}</td>
                        <td class="num font-bold">{{ number_format($return->total, 2) }}</td>
                        <td>{{ $return->destinationLabel() }}</td>
                        <td>{{ $return->refundMethodLabel() }} — <span class="num">{{ number_format($return->refund_amount, 2) }}</span> ج</td>
                        <td><span class="ds-badge ds-badge-neutral">{{ $return->status }}</span></td>
                        <td>{{ $return->reason ?? '—' }}</td>
                        <td>
                            @can('pos.shift')
                                @if ($return->status === 'pending')
                                    <form method="POST" action="{{ route('returns.approve', $return) }}" class="inline">
                                        @csrf <button class="ds-btn ds-btn-sm ds-btn-success">موافقة</button>
                                    </form>
                                    <form method="POST" action="{{ route('returns.reject', $return) }}" class="inline">
                                        @csrf <button class="ds-btn ds-btn-sm ds-btn-danger" style="background: var(--bad); border-color: var(--bad); color: white;">رفض</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="ds-empty">لا توجد مرتجعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $returns->links() }}</div>
</div>
@endsection