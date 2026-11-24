@extends('layouts.app')

@section('title', 'طلب #'.$order->no)
@section('page-title', 'الطلب #'.$order->no)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5 grid gap-2 sm:grid-cols-4">
        <div><div class="text-xs text-slate-500">العميل</div><div class="font-semibold">{{ $order->customer?->name }}</div></div>
        <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $order->status }}</div></div>
        <div><div class="text-xs text-slate-500">الفرع</div><div class="font-semibold">{{ $order->location_id }}</div></div>
        <div><div class="text-xs text-slate-500">الإجمالي</div><div class="font-semibold">{{ $order->total ?? $order->est_total }}</div></div>
    </div>

    @can('orders.handle')
        <div class="ds-card p-5 grid gap-4 lg:grid-cols-3">
            <div>
                <h3 class="font-bold mb-2">نقل الحالة</h3>
                <form method="POST" action="{{ route('orders.status', $order) }}" class="space-y-2">
                    @csrf
                    <select name="status" class="w-full rounded border border-slate-300 px-2 py-1">
                        @foreach (['accepted','preparing','ready','out'] as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    <input name="driver" placeholder="اسم الطيار (للخروج)" class="w-full rounded border border-slate-300 px-2 py-1">
                    <button class="ds-btn ds-btn-primary rounded px-3 py-1 text-sm">تحديث</button>
                </form>
                @can('orders.cancel')
                    <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <input name="reason" placeholder="سبب الإلغاء" class="flex-1 rounded border border-slate-300 px-2 py-1">
                        <button class="text-rose-600 text-sm">إلغاء</button>
                    </form>
                @endcan
            </div>

            <div>
                <h3 class="font-bold mb-2">تحضير بمسح علبة</h3>
                <form method="POST" action="{{ route('orders.scan', $order) }}" class="space-y-2">
                    @csrf
                    <input name="code" placeholder="امسح QR/باركود" class="w-full rounded border border-slate-300 px-2 py-1" dir="ltr">
                    <button class="ds-btn ds-btn-accent rounded px-3 py-1 text-sm">تحضير</button>
                </form>
                <form method="POST" action="{{ route('orders.unscan', $order) }}" class="mt-2 flex gap-2">
                    @csrf
                    <input name="box_sn" placeholder="رقم علبة للتراجع" class="flex-1 rounded border border-slate-300 px-2 py-1">
                    <button class="text-amber-600 text-sm">تراجع</button>
                </form>
            </div>

            <div>
                <h3 class="font-bold mb-2">تسليم وتحصيل</h3>
                <form method="POST" action="{{ route('orders.deliver', $order) }}">
                    @csrf
                    <button class="w-full ds-btn ds-btn-danger bg-red-600 text-white border-red-600 hover:bg-red-700 rounded px-3 py-2 font-bold">تسليم</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr><th class="text-right px-4 py-3">الصنف</th><th class="text-right px-4 py-3">المطلوب</th><th class="text-right px-4 py-3">المحضّر</th><th class="text-right px-4 py-3">السعر</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->lines as $line)
                    <tr>
                        <td class="px-4 py-3">{{ $line->item?->name }}</td>
                        <td class="px-4 py-3">{{ $line->qty }}</td>
                        <td class="px-4 py-3">{{ $line->prepared }}</td>
                        <td class="px-4 py-3">{{ $line->unit_price }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection