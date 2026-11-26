@extends('layouts.app')

@section('title', 'حجز')
@section('page-title', 'حجز ليوم ' . $reservation->reservation_date->toDateString())

@section('content')
<div class="space-y-4">
    @if (session('status'))
        <div class="ds-card p-4 border-green-200 bg-green-50 text-green-700 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="ds-card p-4 border-red-200 bg-red-50 text-red-700 text-sm">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <div class="ds-card p-5 grid gap-4 sm:grid-cols-2">
        <div><span class="text-xs text-slate-400">العميل</span><div class="font-bold">{{ $reservation->customer?->name }} <span class="text-sm font-normal text-slate-500" dir="ltr">{{ $reservation->customer?->phone }}</span></div></div>
        <div><span class="text-xs text-slate-400">الفرع</span><div class="font-bold">{{ $reservation->location?->name ?? $reservation->location_id }}</div></div>
        <div><span class="text-xs text-slate-400">التاريخ</span><div class="font-bold">{{ $reservation->reservation_date->toDateString() }}</div></div>
        <div><span class="text-xs text-slate-400">الحالة</span><div class="font-bold">
            @if ($reservation->status === 'pending') معلق
            @elseif ($reservation->status === 'converted') اتحول لأوردر
            @else ملغي
            @endif
        </div></div>
        <div><span class="text-xs text-slate-400">طريقة الدفع</span><div class="font-bold">{{ $reservation->pay_method }}</div></div>
        <div><span class="text-xs text-slate-400">رسوم التوصيل</span><div class="font-bold">{{ number_format($reservation->delivery_fee, 2) }} ج</div></div>
        @if ($reservation->note)
            <div class="sm:col-span-2"><span class="text-xs text-slate-400">ملاحظات</span><div>{{ $reservation->note }}</div></div>
        @endif
        @if ($reservation->convertedOrder)
            <div class="sm:col-span-2"><span class="text-xs text-slate-400">الأوردر</span><div><a href="{{ route('orders.show', $reservation->convertedOrder) }}" class="ds-link font-bold">أوردر #{{ $reservation->convertedOrder->no }}</a> (اتحول {{ $reservation->converted_at }})</div></div>
        @endif
    </div>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-2">الأصناف والكميات</h3>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>السعر (وقت الحجز)</th></tr></thead>
                <tbody>
                    @foreach ($reservation->lines as $line)
                        <tr>
                            <td class="font-semibold">{{ $line->item?->name }}</td>
                            <td class="num">{{ number_format($line->qty, 3) }} {{ $line->item?->unit }}</td>
                            <td class="num">{{ $line->unit_price !== null ? number_format($line->unit_price, 2) . ' ج' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($reservation->status === 'pending')
        <div class="flex gap-2">
            <form method="POST" action="{{ route('reservations.convert', $reservation) }}" onsubmit="return confirm('أكدت مع العميل؟ هيتحول لأوردر دلوقتي.')">
                @csrf
                <button class="ds-btn ds-btn-primary rounded px-4 py-2 font-bold">حوّل اوردر</button>
            </form>
            <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('إلغاء الحجز؟')">
                @csrf
                <button class="ds-btn rounded px-4 py-2 font-bold">إلغاء الحجز</button>
            </form>
        </div>
        <p class="text-xs text-slate-400">لو متحولش يدوي، هيتحول لوحده الساعة 12 بالليل في تاريخ الحجز.</p>
    @endif

    <a href="{{ route('reservations.index') }}" class="text-sm text-slate-500">رجوع للحجوزات</a>
</div>
@endsection
