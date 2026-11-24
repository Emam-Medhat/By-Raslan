@extends('layouts.app')

@section('title', 'تفاصيل الشيفت #'.$shift->id)
@section('page-title', 'تفاصيل الشيفت #'.$shift->id)

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">تفاصيل الشيفت #{{ $shift->id }}</h1>
            <p class="text-sm text-slate-500">
                {{ $shift->cashierUser?->name }} · {{ $shift->location?->name }}
                · <span class="inline-block rounded px-2 py-0.5 text-xs {{ $shift->status === 'open' ? 'bg-emerald-100 text-emerald-700' : ($shift->status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600') }}">{{ $shift->status === 'open' ? 'مفتوح' : ($shift->status === 'paused' ? 'موقوف' : 'مغلق') }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('shifts.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
            <a href="{{ route('shifts.movements', $shift) }}" class="ds-btn ds-btn-primary rounded-lg px-4 py-2 text-sm font-bold">📊 حركة</a>
            @if($shift->status === 'open')
                <button onclick="document.getElementById('closeModal').classList.remove('hidden')" class="ds-btn ds-btn-danger bg-red-600 text-white border-red-600 hover:bg-red-700 rounded-lg px-4 py-2 text-sm font-bold">إغلاق الوردية</button>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ds-card p-4">
            <div class="text-xs text-slate-500">رصيد البداية</div>
            <div class="text-xl font-extrabold mt-1">{{ number_format((float) $shift->opening_cash, 2) }} ج</div>
            <div class="h-1 w-10 bg-accent-500 rounded mt-2"></div>
        </div>
        <div class="ds-card p-4">
            <div class="text-xs text-slate-500">المتوقع</div>
            <div class="text-xl font-extrabold mt-1">{{ number_format($expected, 2) }} ج</div>
            <div class="h-1 w-10 bg-emerald-600 rounded mt-2"></div>
        </div>
        <div class="ds-card p-4">
            <div class="text-xs text-slate-500">المعدود</div>
            <div class="text-xl font-extrabold mt-1">{{ $shift->counted_cash !== null ? number_format((float) $shift->counted_cash, 2) . ' ج' : '—' }}</div>
            <div class="h-1 w-10 bg-sky-600 rounded mt-2"></div>
        </div>
        <div class="ds-card p-4">
            <div class="text-xs text-slate-500">الفرق</div>
            <div class="text-xl font-extrabold mt-1 {{ $difference === null ? '' : ($difference < 0 ? 'text-rose-600' : ($difference > 0 ? 'text-emerald-600' : '')) }}">
                {{ $difference === null ? '—' : number_format($difference, 2) . ' ج' }}
            </div>
            <div class="h-1 w-10 {{ $difference === null ? 'bg-slate-300' : ($difference < 0 ? 'bg-rose-500' : ($difference > 0 ? 'bg-emerald-500' : 'bg-emerald-500')) }} rounded mt-2"></div>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="ds-card p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div><div class="text-xs text-slate-500">الموظف</div><div class="font-semibold">{{ $shift->cashierUser?->name }}</div></div>
            <div><div class="text-xs text-slate-500">الفرع</div><div class="font-semibold">{{ $shift->location?->name }}</div></div>
            <div><div class="text-xs text-slate-500">البداية</div><div class="font-semibold">{{ $shift->opened_at?->format('Y-m-d H:i') }}</div></div>
            <div><div class="text-xs text-slate-500">النهاية</div><div class="font-semibold">{{ $shift->closed_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
            <div><div class="text-xs text-slate-500">أغلقها</div><div class="font-semibold">{{ $shift->closedBy?->name ?? '—' }}</div></div>
        </div>
        @if($shift->notes)
            <div class="mt-3 pt-3 border-t border-slate-100">
                <div class="text-xs text-slate-500">ملاحظات</div>
                <div class="text-sm">{{ $shift->notes }}</div>
            </div>
        @endif
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
                @forelse($sales as $sale)
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
                @forelse($shiftExpenses as $expense)
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
                @forelse($cashTransfers as $transfer)
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
</div>

{{-- Close Shift Modal --}}
<div id="closeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="ds-btn ds-btn-danger bg-red-600 text-white border-red-600 hover:bg-red-700 px-5 py-4 flex items-center gap-3">
            <span class="text-xl">🔒</span>
            <h3 class="font-bold">إغلاق الوردية</h3>
        </div>
        <form method="POST" action="{{ route('shifts.close', $shift) }}" class="p-5 space-y-4">
            @csrf
            <div class="bg-slate-50 rounded-lg p-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">المتوقع</span><span class="font-bold">{{ number_format($expected, 2) }} ج</span></div>
            </div>
            <p class="text-sm text-slate-500">أدخل النقد المعدود الفعلي في نهاية الشيفت.</p>
            <div>
                <label class="block text-sm font-semibold mb-1">الرصيد المعدود</label>
                <input name="counted_cash" type="number" step="0.01" min="0" required class="w-full ds-input" placeholder="0.00">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات (اختياري)</label>
                <textarea name="notes" rows="2" class="w-full ds-input" placeholder="أي ملاحظات..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 ds-btn ds-btn-danger bg-red-600 text-white border-red-600 hover:bg-red-700 rounded-lg py-2 font-bold">تأكيد الإغلاق</button>
                <button type="button" onclick="document.getElementById('closeModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-slate-300">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection