@extends('layouts.guest')

@section('title', 'تتبّع الطلب #'.$order->no)

@section('content')
<div class="w-full max-w-xl bg-white rounded-2xl shadow-lg p-6">
    <h1 class="text-xl font-extrabold text-accent-700 mb-2">الطلب #{{ $order->no }}</h1>
    <div class="text-sm text-slate-500 mb-4">الحالة: <span class="font-bold text-slate-800">{{ $order->status }}</span></div>

    <div class="divide-y divide-slate-100">
        @foreach ($order->lines as $line)
            <div class="flex justify-between py-2 text-sm">
                <span>{{ $line->item?->name }} ×{{ $line->qty }}</span>
                <span>{{ $line->unit_price }} ج</span>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t pt-3 text-sm">
        <div class="flex justify-between"><span>التوصيل</span><span>{{ $order->delivery_fee }} ج</span></div>
        @if ($order->total !== null)
            <div class="flex justify-between font-bold mt-1"><span>الإجمالي</span><span>{{ $order->total }} ج</span></div>
        @else
            <div class="flex justify-between mt-1"><span>ال تقديري</span><span>{{ $order->est_total }} ج</span></div>
        @endif
    </div>
</div>
@endsection