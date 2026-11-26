@extends('layouts.app')

@section('title', 'محطة التغليف')
@section('page-title', 'محطة التغليف')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <div class="ds-card p-5">
        <h3 class="font-bold mb-3">تغليف علبة (امسح ملصق الميزان)</h3>
        <p class="text-sm text-slate-500 mb-3">باركود الميزان 13 رقماً: الأول والآخر مهمشان، ثم كود الصنف 6 أرقام، ثم الوزن 5 أرقام (رقمان للكيلو + 3 للجرام) — مثال: كود 000110 ووزن 0.460 كجم. كود الصنف لازم يطابق الكود المدخل في السيستم، وإلا لن تُنشأ العلبة.</p>
        <form method="POST" action="{{ route('pack.store') }}" class="space-y-3">
            @csrf
            <select name="order_id" class="ds-input">
                <option value="">— بدون أمر —</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>#{{ $order->no }} — {{ $order->item?->name }}</option>
                @endforeach
            </select>
            <input name="code" value="{{ old('code') }}" placeholder="امسح باركود الميزان أو مثال: 5|12.5" class="ds-input" dir="ltr" autofocus autocomplete="off">
            <div>
                <label class="ds-label">الفرع / الموقع الذي ستذهب إليه العلبة</label>
                <select name="location_id" class="ds-input">
                    <option value="">المصنع (افتراضي)</option>
                    @foreach ($locations ?? [] as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="ds-label">تاريخ الإنتاج</label>
                    <input type="date" name="packed_at" value="{{ old('packed_at', now()->toDateString()) }}" class="ds-input">
                </div>
                <div>
                    <label class="ds-label">تاريخ الصلاحية (اختياري)</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="ds-input">
                </div>
            </div>
            <button class="ds-btn ds-btn-primary">تغليف</button>
        </form>

        @isset($lastBox)
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm">
                <div class="font-bold text-emerald-800 mb-2">آخر علبة تم تغليفها</div>
                <div class="space-y-1 text-slate-700">
                    <div>الصنف: <span class="font-bold">{{ $lastBox->item?->name ?? '—' }}</span></div>
                    <div>كود الصنف: <span class="font-bold" dir="ltr">{{ $lastBox->item?->scale_plu ?? '—' }}</span></div>
                    <div>الوزن: <span class="font-bold">{{ number_format((float) $lastBox->weight, 3) }} كجم</span></div>
                    <div>الرقم المميز: <span class="font-bold text-lg" dir="ltr">#{{ $lastBox->sn }}</span></div>
                    <div>الموقع: <span class="font-bold">{{ $lastBox->location?->name ?? $lastBox->location_id }}</span></div>
                    <div>تاريخ الإنتاج: <span class="font-bold">{{ $lastBox->packed_at?->format('Y-m-d') ?? '—' }}</span></div>
                    <div>تاريخ الصلاحية: <span class="font-bold">{{ $lastBox->expires_at?->format('Y-m-d') ?? '—' }}</span></div>
                </div>
                <a href="{{ route('boxes.sticker', $lastBox->sn) }}" target="_blank" class="ds-btn ds-btn-secondary mt-3">طباعة ستيكر العلبة</a>
            </div>
        @endisset
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

<script>
    // بعد كل تغليف ناجح الصفحة تُعاد تحميلها والخانة فاضية — ركّز عليها للمسح التالي.
    document.querySelector('input[name="code"]')?.focus();
</script>
@endsection
