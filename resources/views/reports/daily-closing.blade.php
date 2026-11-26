@extends('layouts.app')

@section('title', 'إقفال اليومية')
@section('page-title', 'إقفال اليومية')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('reports.daily-closing') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="ds-label">التاريخ</label>
            <input type="date" name="date" value="{{ $report['date'] }}" class="ds-input">
        </div>
        <div>
            <label class="ds-label">الفرع</label>
            <select name="location_id" class="ds-input">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected($location->id === $report['location_id'])>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">عرض</button>
    </form>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h1 class="text-xl font-extrabold">إقفال يومية {{ $report['location']?->name ?? '—' }} — {{ $report['date'] }}</h1>
        <p class="text-sm text-slate-500 mt-1">
            من الساعة {{ $report['from_time'] ? \Carbon\Carbon::parse($report['from_time'])->format('H:i') : '—' }}
            إلى الساعة {{ $report['to_time'] ? \Carbon\Carbon::parse($report['to_time'])->format('H:i') : '—' }}
            — {{ $report['orders_count'] }} طلب ({{ $report['pos_count'] }} كاشير + {{ $report['online_count'] }} أونلاين)
        </p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">عهدة أول اليوم (مرحّلة)</div><div class="num text-lg font-extrabold">{{ number_format($report['opening_custody'], 2) }} ج</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">إجمالي المبيعات</div><div class="num text-lg font-extrabold">{{ number_format($report['gross'], 2) }} ج</div></div>
            <div class="rounded-xl bg-red-50 p-3"><div class="text-xs text-red-500">مرتجعات ({{ $report['returns_count'] }})</div><div class="num text-lg font-extrabold text-red-600">− {{ number_format($report['returns_total'], 2) }} ج</div></div>
            <div class="rounded-xl bg-emerald-50 p-3"><div class="text-xs text-emerald-600">الصافي بعد المرتجعات</div><div class="num text-lg font-extrabold text-emerald-700">{{ number_format($report['net'], 2) }} ج</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">تحصيل كاش (بعد استرداد الكاش)</div><div class="num text-lg font-extrabold">{{ number_format($report['cash_collected'], 2) }} ج</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">تحصيل غير كاش (إنستا باي / فيزا)</div><div class="num text-lg font-extrabold">{{ number_format($report['noncash_collected'], 2) }} ج</div></div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">المصروفات</div><div class="num text-lg font-extrabold">{{ number_format($report['expenses'], 2) }} ج</div></div>
            <div class="rounded-xl bg-primary-800 text-white p-3"><div class="text-xs opacity-80">العهدة المتوقعة آخر اليوم</div><div class="num text-lg font-extrabold">{{ number_format($report['expected_custody'], 2) }} ج</div><div class="text-[11px] opacity-80 mt-1">ده الكاش اللي المفروض يكون معاك — السيستم حسبه من الأوردرات، مش من سؤالك</div></div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-extrabold mb-3">الأوردرات ({{ $report['orders_count'] }}) — كل أوردر بمبلغه وطريقة تحصيله</h2>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>الوقت</th><th>النوع</th><th>رقم</th><th>العميل</th><th>الإجمالي</th><th>طريقة التحصيل</th><th>كاش</th><th>غير كاش</th></tr></thead>
                <tbody>
                    @forelse ($report['orders'] as $order)
                        <tr>
                            <td>{{ $order['time'] ? \Carbon\Carbon::parse($order['time'])->format('H:i') : '—' }}</td>
                            <td>{{ $order['kind_label'] }}</td>
                            <td class="font-semibold">#{{ $order['no'] }}</td>
                            <td>{{ $order['customer'] ?? '—' }}</td>
                            <td class="num font-bold">{{ number_format($order['total'], 2) }}</td>
                            <td>{{ $order['method_label'] }}</td>
                            <td class="num">{{ number_format($order['cash'], 2) }}</td>
                            <td class="num">{{ number_format($order['noncash'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ds-empty">لا توجد أوردرات في اليوم ده</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-extrabold mb-3">المرتجعات ({{ $report['returns_count'] }}) — منفصلة عن المبيعات</h2>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>الوقت</th><th>رقم المرتجع</th><th>الفاتورة</th><th>الوجهة</th><th>الاسترداد</th><th>المبلغ</th></tr></thead>
                <tbody>
                    @forelse ($report['returns'] as $return)
                        <tr>
                            <td>{{ $return->decided_at?->format('H:i') ?? '—' }}</td>
                            <td class="font-semibold">R{{ $return->no }}</td>
                            <td>#{{ $return->sale?->no }}</td>
                            <td>{{ $return->destinationLabel() }}</td>
                            <td>{{ $return->refundMethodLabel() }} — <span class="num">{{ number_format($return->refund_amount, 2) }}</span> ج</td>
                            <td class="num font-bold text-red-600">− {{ number_format($return->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ds-empty">لا توجد مرتجعات في اليوم ده</td></tr>
                    @endforelse
                    @if ($report['returns_count'] > 0)
                        <tr class="font-bold">
                            <td colspan="5">الإجمالي المرتجع</td>
                            <td class="num text-red-600">− {{ number_format($report['returns_total'], 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <p class="text-sm text-slate-500 mt-3">اتباع {{ $report['orders_count'] }} طلب، واترجع {{ $report['returns_count'] }} — الصافي {{ $report['orders_count'] - $report['returns_count'] }} طلب بقيمة {{ number_format($report['net'], 2) }} ج، والمرتجع مكتوب لوحده فوق عشان الحسابات تظبط.</p>
    </div>
</div>
@endsection
