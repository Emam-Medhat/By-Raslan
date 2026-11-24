@extends('layouts.guest')

@section('title', 'تتبع الطلب')

@section('content')
@php
    $steps = [['new','وصلنا طلبك'],['accepted','اتقبل'],['preparing','بيتحضّر'],['ready','جاهز'],['out','مع الطيار'],['delivered','اتسلّم']];
    $idx = collect($steps)->search(fn($s) => $s[0] === $order->status);
    $idx = $idx === false ? -1 : $idx;
    $final = $order->total !== null;
    $lineTotal = fn($l) => (($l->prepared > 0 ? $l->prepared : $l->qty) * $l->unit_price);
    $sub = $order->lines->sum($lineTotal);
@endphp
<div class="max-w-3xl mx-auto p-4 space-y-4">
    <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">طلب رقم {{ $order->no }}</h1>
            <span class="text-sm text-slate-500">{{ $order->location?->name ?? $order->location_id }}</span>
        </div>

        @if ($order->status === 'cancelled')
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 p-3">الطلب اتلغى {{ $order->cancel_reason ? '— '.$order->cancel_reason : '' }}</div>
        @else
            <div class="grid sm:grid-cols-6 gap-2 mb-4">
                @foreach ($steps as $i => $s)
                    <div class="rounded-lg px-2 py-2 text-center text-xs border {{ $i <= $idx ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-500' }}">{{ $s[1] }}</div>
                @endforeach
            </div>
        @endif

        <div class="divide-y divide-slate-100">
            @foreach ($order->lines as $l)
                <div class="py-2 flex justify-between gap-3 text-sm">
                    <span>{{ $l->item?->name }} <span class="text-slate-500">{{ $l->prepared > 0 ? $l->prepared : $l->qty }} {{ $l->item?->unit }}</span></span>
                    <b>{{ number_format($lineTotal($l), 2) }}</b>
                </div>
            @endforeach
        </div>
        <div class="mt-3 pt-3 border-t border-slate-200 space-y-1 text-sm">
            <div class="flex justify-between"><span>التوصيل</span><b>{{ number_format($order->delivery_fee, 2) }}</b></div>
            <div class="flex justify-between text-lg font-bold"><span>{{ $final ? 'الإجمالي' : 'الإجمالي التقريبي' }}</span><span>{{ number_format($final ? $order->total : $sub + $order->delivery_fee, 2) }} ج</span></div>
        </div>
    </div>
    <p class="text-xs text-slate-500 text-center">احفظ الرابط لمتابعة الطلب.</p>
</div>
@endsection
