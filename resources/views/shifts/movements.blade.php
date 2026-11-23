@extends('layouts.app')

@section('title', 'كشف حركة الشيفت #'.$shift->id)
@section('page-title', 'كشف حركة الشيفت #'.$shift->id)

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div><div class="text-xs text-slate-500">الموظف</div><div class="font-semibold">{{ $shift->cashierUser?->name }}</div></div>
        <div><div class="text-xs text-slate-500">الفرع</div><div class="font-semibold">{{ $shift->location?->name }}</div></div>
        <div><div class="text-xs text-slate-500">البداية</div><div class="font-semibold">{{ $shift->opened_at }}</div></div>
        <div><div class="text-xs text-slate-500">النهاية</div><div class="font-semibold">{{ $shift->closed_at ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $shift->status === 'open' ? 'مفتوح' : 'مغلق' }}</div></div>
    </div>

    {{-- Reconciliation cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['رصيد البداية', $shift->opening_cash, 'bg-brand-600'],
            ['نقدي المبيعات', number_format($cashSales, 2), 'bg-emerald-600'],
            ['فيزا', number_format($cardSales, 2), 'bg-sky-600'],
            ['المصروفات', number_format($expensesTotal, 2), 'bg-rose-500'],
        ] as [$label, $value, $color])
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $value }}</div>
                <div class="h-1 w-10 {{ $color }} rounded mt-2"></div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 grid gap-2 sm:grid-cols-3 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">المتوقع</span><span class="font-bold">{{ number_format($expected, 2) }} ج</span></div>
        <div class="flex justify-between"><span class="text-slate-500">المعدود</span><span class="font-bold">{{ $shift->counted_cash !== null ? number_format($shift->counted_cash, 2) : '—' }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">الفرق</span>
            @php($diff = $shift->counted_cash !== null ? round((float)$shift->counted_cash - $expected, 2) : null)
            <span class="font-bold {{ $diff === null ? '' : ($diff < 0 ? 'text-rose-600' : ($diff > 0 ? 'text-emerald-600' : '')) }}">{{ $diff === null ? '—' : number_format($diff, 2) }}</span>
        </div>
    </div>

    {{-- Sales --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">المبيعات</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-2">الفاتورة</th><th class="text-right px-3 py-2">الإجمالي</th>
                    <th class="text-right px-3 py-2">نقدي</th><th class="text-right px-3 py-2">فيزا</th><th class="text-right px-3 py-2">الباقي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sales as $sale)
                    <tr>
                        <td class="px-3 py-2">#{{ $sale->no }}</td>
                        <td class="px-3 py-2">{{ $sale->total }}</td>
                        <td class="px-3 py-2">{{ $sale->paid_cash }}</td>
                        <td class="px-3 py-2">{{ $sale->paid_card }}</td>
                        <td class="px-3 py-2">{{ $sale->change }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-400">لا توجد مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Stock moves --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">حركات المخزون</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-right px-3 py-2">الصنف</th><th class="text-right px-3 py-2">النوع</th><th class="text-right px-3 py-2">الكمية</th><th class="text-right px-3 py-2">المرجع</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stockMoves as $move)
                    <tr>
                        <td class="px-3 py-2">{{ $move->item_id }}</td>
                        <td class="px-3 py-2">{{ $move->kind }}</td>
                        <td class="px-3 py-2">{{ $move->qty }}</td>
                        <td class="px-3 py-2">{{ $move->ref }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-400">لا توجد حركات مخزنية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Expenses --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">المصروفات</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-right px-3 py-2">البند</th><th class="text-right px-3 py-2">المبلغ</th><th class="text-right px-3 py-2">الطريقة</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-3 py-2">{{ $expense->category?->name }}</td>
                        <td class="px-3 py-2">{{ $expense->amount }}</td>
                        <td class="px-3 py-2">{{ $expense->paid_from }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-3 py-4 text-center text-slate-400">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection