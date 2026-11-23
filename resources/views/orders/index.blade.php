@extends('layouts.app')

@section('title', 'الطلبات')
@section('page-title', 'طلبات الأونلاين')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">الطلبات</h1>
    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">الكل</option>
            @foreach (\App\Modules\Orders\Models\OnlineOrder::STATUSES as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">العميل</th>
                <th class="text-right px-4 py-3">الفرع</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">الإجمالي التقديري</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $order->no }}</td>
                    <td class="px-4 py-3">{{ $order->customer?->name }}<div class="text-xs text-slate-400">{{ $order->customer?->phone }}</div></td>
                    <td class="px-4 py-3">{{ $order->location_id }}</td>
                    <td class="px-4 py-3">{{ $order->status }}</td>
                    <td class="px-4 py-3">{{ $order->est_total }}</td>
                    <td class="px-4 py-3"><a href="{{ route('orders.show', $order) }}" class="underline text-brand-700">فتح</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد طلبات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection