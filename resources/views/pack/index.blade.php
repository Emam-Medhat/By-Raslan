@extends('layouts.app')

@section('title', 'محطة التغليف/الإنتاج')
@section('page-title', 'إدخال إنتاج وتحويل الأطباق للفروع')

@section('content')
<div class="space-y-4">
    @if(session('status'))
        <div class="ds-alert ds-alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="ds-alert ds-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="ds-card p-5">
            <h3 class="font-bold mb-3">قراءة باركود الميزان وإنشاء طبق</h3>
            <form method="POST" action="{{ route('pack.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="ds-label">الفرع/الموقع الذي ستدخل فيه الأطباق</label>
                    <select name="location_id" class="ds-input" required>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected(old('location_id', 'factory') == $loc->id)>{{ $loc->name }} ({{ $loc->type }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-label">أمر الإنتاج (اختياري)</label>
                    <select name="order_id" class="ds-input">
                        <option value="">— بدون أمر —</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected(old('order_id')==$order->id)>#{{ $order->no }} — {{ $order->item?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-label">باركود الميزان</label>
                    <input name="code" value="{{ old('code') }}" placeholder="امسح باركود الميزان هنا" class="ds-input" dir="ltr" autofocus autocomplete="off">
                    <p class="text-xs text-slate-500 mt-1">يدعم تنسيق الميزان (كود الصنف + 5 أرقام وزن: 2 كجم + 3 جرام) وأيضًا `item|weight`.</p>
                </div>
                <button class="ds-btn ds-btn-primary w-full">إنشاء الطبق وطباعة الاستيكر</button>
            </form>
        </div>

        <div class="ds-card p-5">
            <h3 class="font-bold mb-3">آخر طبق تم إنشاؤه</h3>
            @if(!empty($lastBox))
                <div class="space-y-2 text-sm">
                    <div><span class="text-slate-500">الرقم المميز:</span> <strong class="font-mono">{{ $lastBox->uid ?: $lastBox->sn }}</strong></div>
                    <div><span class="text-slate-500">الصنف:</span> {{ $lastBox->item?->name }}</div>
                    <div><span class="text-slate-500">كود الصنف:</span> {{ $lastBox->item?->scale_plu ?: $lastBox->item_id }}</div>
                    <div><span class="text-slate-500">الوزن:</span> {{ number_format((float)$lastBox->weight,3) }} KG</div>
                    <div><span class="text-slate-500">تاريخ الإنتاج:</span> {{ optional($lastBox->produced_at ?? $lastBox->packed_at)->format('Y-m-d H:i') }}</div>
                    <div><span class="text-slate-500">الصلاحية:</span> {{ (int)($lastBox->item?->shelf_life_days ?? 0) }} يوم @if($lastBox->expires_at) — {{ $lastBox->expires_at->format('Y-m-d') }} @endif</div>
                    <div><span class="text-slate-500">الفرع:</span> {{ $lastBox->location?->name ?? $lastBox->location_id }}</div>
                    <div class="pt-2">
                        <a href="{{ route('boxes.sticker', $lastBox) }}" target="_blank" class="ds-btn ds-btn-accent">طباعة الاستيكر / QR</a>
                    </div>
                </div>
            @else
                <div class="text-slate-500 text-sm">لم يتم إنشاء طبق في هذه الجلسة بعد.</div>
            @endif

            <hr class="my-4">
            <h3 class="font-bold mb-3">إنهاء التغليف لأمر</h3>
            <form method="POST" action="{{ route('pack.finish') }}" class="space-y-3">
                @csrf
                <select name="order_id" class="ds-input">
                    @foreach ($orders as $order)
                        <option value="{{ $order->id }}">#{{ $order->no }} — {{ $order->item?->name }}</option>
                    @endforeach
                </select>
                <button class="ds-btn ds-btn-secondary">إنهاء</button>
            </form>
        </div>
    </div>
</div>
@endsection
