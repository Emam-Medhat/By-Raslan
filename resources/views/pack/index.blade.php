@extends('layouts.app')

@section('title', 'محطة التغليف')
@section('page-title', 'محطة التغليف')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-bold mb-3">تغليف علبة (امسح ملصق الميزان)</h3>
        <form method="POST" action="{{ route('pack.store') }}" class="space-y-3">
            @csrf
            <select name="order_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">— بدون أمر —</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}">#{{ $order->no }} — {{ $order->item?->name }}</option>
                @endforeach
            </select>
            <input name="code" placeholder="مثال: 5|12.5" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            <button class="bg-brand-600 text-white rounded-lg px-5 py-2 font-bold">تغليف</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-bold mb-3">إنهاء التغليف لأمر</h3>
        <form method="POST" action="{{ route('pack.finish') }}" class="space-y-3">
            @csrf
            <select name="order_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}">#{{ $order->no }} — {{ $order->item?->name }}</option>
                @endforeach
            </select>
            <button class="bg-emerald-600 text-white rounded-lg px-5 py-2 font-bold">إنهاء</button>
        </form>
    </div>
</div>
@endsection