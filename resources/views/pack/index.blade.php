@extends('layouts.app')

@section('title', 'محطة التغليف')
@section('page-title', 'محطة التغليف')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <div class="ds-card p-5">
        <h3 class="font-bold mb-3">تغليف علبة (امسح ملصق الميزان)</h3>
        <form method="POST" action="{{ route('pack.store') }}" class="space-y-3">
            @csrf
            <select name="order_id" class="ds-input">
                <option value="">— بدون أمر —</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}">#{{ $order->no }} — {{ $order->item?->name }}</option>
                @endforeach
            </select>
            <input name="code" placeholder="مثال: 5|12.5" class="ds-input" dir="ltr">
            <button class="ds-btn ds-btn-primary">تغليف</button>
        </form>
    </div>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-3">إنهاء التغليف لأمر</h3>
        <form method="POST" action="{{ route('pack.finish') }}" class="space-y-3">
            @csrf
            <select name="order_id" class="ds-input">
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}">#{{ $order->no }} — {{ $order->item?->name }}</option>
                @endforeach
            </select>
            <button class="ds-btn ds-btn-accent">إنهاء</button>
        </form>
    </div>
</div>
@endsection