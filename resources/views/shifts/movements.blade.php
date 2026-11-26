@extends('layouts.app')

@section('title', 'كشف حركة الشيفت #'.$shift->id)
@section('page-title', 'كشف حركة الشيفت #'.$shift->id)

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">كشف حركة الشيفت #{{ $shift->id }}</h1>
            <p class="text-sm text-slate-500">{{ $shift->cashierUser?->name }} · {{ $shift->location?->name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('shifts.show', $shift) }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ تفاصيل</a>
            <a href="{{ route('shifts.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="ds-card p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div><div class="text-xs text-slate-500">الموظف</div><div class="font-semibold">{{ $shift->cashierUser?->name }}</div></div>
            <div><div class="text-xs text-slate-500">الفرع</div><div class="font-semibold">{{ $shift->location?->name }}</div></div>
            <div><div class="text-xs text-slate-500">البداية</div><div class="font-semibold">{{ $shift->opened_at?->format('Y-m-d H:i') }}</div></div>
            <div><div class="text-xs text-slate-500">النهاية</div><div class="font-semibold">{{ $shift->closed_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
            <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $shift->status === 'open' ? 'مفتوح' : 'مغلق' }}</div></div>
        </div>
    </div>

    {{-- Reconciliation Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['رصيد البداية', number_format((float) $shift->opening_cash, 2) . ' ج', 'bg-accent-500'],
            ['نقدي المبيعات', number_format($cashSales, 2) . ' ج', 'bg-emerald-600'],
            ['فيزا', number_format($cardSales, 2) . ' ج', 'bg-sky-600'],
            ['المصروفات', number_format($shiftExpensesTotal + $expensesTotal, 2) . ' ج', 'bg-rose-500'],
        ] as [$label, $value, $color])
            <div class="ds-card p-4">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-xl font-extrabold mt-1">{{ $value }}</div>
                <div class="h-1 w-10 {{ $color }} rounded mt-2"></div>
            </div>
        @endforeach
    </div>

    <div class="ds-card p-4 grid gap-2 sm:grid-cols-3 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">المتوقع</span><span class="font-bold">{{ number_format($expected, 2) }} ج</span></div>
        <div class="flex justify-between"><span class="text-slate-500">رصيد البداية</span><span class="font-bold">{{ number_format((float) $shift->opening_cash, 2) }} ج</span></div>
        <div class="flex justify-between"><span class="text-slate-500">المحول للمدير</span><span class="font-bold">{{ number_format($transfersTotal, 2) }} ج</span></div>
    </div>

    {{-- Sales --}}
    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">المبيعات ({{ $sales->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-2">الفاتورة</th>
                    <th class="text-right px-3 py-2">الإجمالي</th>
                    <th class="text-right px-3 py-2">نقدي</th>
                    <th class="text-right px-3 py-2">فيزا</th>
                    <th class="text-right px-3 py-2">الباقي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sales as $sale)
                    <tr>
                        <td class="px-3 py-2">#{{ $sale->no }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $sale->total, 2) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $sale->paid_cash, 2) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $sale->paid_card, 2) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $sale->change, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-400">لا توجد مبيعات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Shift Expenses --}}
    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">مصروفات الوردية ({{ $shiftExpenses->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-2">البند</th>
                    <th class="text-right px-3 py-2">المبلغ</th>
                    <th class="text-right px-3 py-2">الحالة</th>
                    <th class="text-right px-3 py-2">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shiftExpenses as $expense)
                    <tr>
                        <td class="px-3 py-2">{{ $expense->title }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $expense->amount, 2) }}</td>
                        <td class="px-3 py-2">
                            <span class="text-xs rounded px-2 py-0.5 {{ $expense->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($expense->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                {{ $expense->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $expense->expense_date?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-400">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Cash Transfers --}}
    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">التحويلات النقدية ({{ $cashTransfers->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-2">النوع</th>
                    <th class="text-right px-3 py-2">المبلغ</th>
                    <th class="text-right px-3 py-2">إلى</th>
                    <th class="text-right px-3 py-2">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cashTransfers as $transfer)
                    <tr>
                        <td class="px-3 py-2">{{ $transfer->getTypeLabel() }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format((float) $transfer->amount, 2) }}</td>
                        <td class="px-3 py-2">{{ $transfer->toUser?->name ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="text-xs rounded px-2 py-0.5 {{ $transfer->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $transfer->getStatusLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-400">لا توجد تحويلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Stock Moves --}}
    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">حركات المخزون ({{ $stockMoves->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
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
</div>
@endsection
