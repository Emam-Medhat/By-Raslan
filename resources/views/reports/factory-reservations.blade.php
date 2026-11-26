@extends('layouts.app')

@section('title', 'تقرير المصنع — حجوزات ' . $report['date'])
@section('page-title', 'تقرير المصنع — حجوزات ' . $report['date'])

@section('content')
<div class="space-y-4">
    <p class="text-sm text-slate-500">الكميات اللي المصنع لازم يجهزها لتاريخ الحجز، مجمعة لكل صنف من كل الحجوزات (المعلقة واللي اتحولت لأوردر — الملغية مش محسوبة). التقرير بيتولد لوحده الساعة 12 بالليل لليوم اللي بعده، والصفحة دي بتعرض نفس التجميع مباشرة لأي تاريخ تختاره.</p>

    <form method="GET" class="ds-card p-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="ds-label">تاريخ الحجوزات</label>
            <input type="date" name="date" value="{{ $report['date'] }}" class="ds-input">
        </div>
        <button class="ds-btn rounded px-4 py-2 font-bold">عرض</button>
        @if ($generated)
            <span class="text-xs text-slate-400">آخر توليد مجدول: {{ $generated['generated_at'] }}</span>
        @else
            <span class="text-xs text-slate-400">لسه متولدش بالتجدول لليوم ده — المعروض تجميع مباشر من الحجوزات.</span>
        @endif
    </form>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-2">الكميات المطلوبة لكل صنف — {{ $report['date'] }}</h3>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>الصنف</th><th>الكمية الإجمالية</th><th>عدد الحجوزات</th><th>توزيع الفروع</th></tr></thead>
                <tbody>
                    @forelse ($report['items'] as $item)
                        <tr>
                            <td class="font-semibold">{{ $item['name'] }}</td>
                            <td class="num font-bold">{{ number_format($item['total_qty'], 3) }} {{ $item['unit'] }}</td>
                            <td class="num">{{ $item['reservations'] }}</td>
                            <td class="text-sm">
                                @foreach ($item['branches'] as $branch => $qty)
                                    <span class="inline-block mr-2">{{ $branch }}: {{ number_format($qty, 3) }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ds-empty">مفيش حجوزات في التاريخ ده</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><td class="font-bold">الإجمالي</td><td class="num font-bold">{{ number_format($report['total_qty'], 3) }}</td><td colspan="2"></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="ds-card p-5">
        <h3 class="font-bold mb-2">الحجوزات والملاحظات</h3>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>العميل</th><th>الفرع</th><th>الأصناف</th><th>الملاحظات</th></tr></thead>
                <tbody>
                    @forelse ($report['reservations'] as $reservation)
                        <tr>
                            <td>{{ $reservation->customer?->name }}<div class="text-xs muted" dir="ltr">{{ $reservation->customer?->phone }}</div></td>
                            <td>{{ $reservation->location?->name ?? $reservation->location_id }}</td>
                            <td class="text-sm">
                                @foreach ($reservation->lines as $line)
                                    <div>{{ $line->item?->name }}: {{ number_format($line->qty, 3) }} {{ $line->item?->unit }}</div>
                                @endforeach
                            </td>
                            <td>{{ $reservation->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ds-empty">مفيش حجوزات في التاريخ ده</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
