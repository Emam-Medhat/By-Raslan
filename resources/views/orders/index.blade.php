@extends('layouts.app')

@section('title', 'الطلبات')
@section('page-title', 'طلبات الأونلاين')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">الطلبات</h1>
        <form method="GET" class="flex gap-2">
            <select name="status" onchange="this.form.submit()" class="ds-input text-sm">
                <option value="">الكل</option>
                @foreach (\App\Modules\Orders\Models\OnlineOrder::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>العميل</th>
                    <th>الفرع</th>
                    <th>الحالة</th>
                    <th>الإجمالي التقديري</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-semibold">{{ $order->no }}</td>
                        <td>
                            {{ $order->customer?->name }}
                            <div class="text-xs muted">{{ $order->customer?->phone }}</div>
                        </td>
                        <td>{{ $order->location_id }}</td>
                        <td><span class="ds-badge ds-badge-neutral">{{ $order->status }}</span></td>
                        <td class="num font-bold">{{ number_format($order->est_total, 2) }}</td>
                        <td><a href="{{ route('orders.show', $order) }}" class="ds-link">فتح</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد طلبات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection