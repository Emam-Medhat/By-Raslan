@extends('layouts.app')

@section('title', 'كشف حساب مورد')
@section('page-title', 'كشف حساب: ' . $supplier->name)

@section('content')
<div class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-sm text-slate-500">المشتريات</div><div class="text-2xl font-extrabold">{{ number_format($purchases,2) }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-sm text-slate-500">المدفوع</div><div class="text-2xl font-extrabold">{{ number_format($paid,2) }}</div></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><div class="text-sm text-slate-500">الرصيد</div><div class="text-2xl font-extrabold">{{ number_format($balance,2) }}</div></div>
    </div>

    @can('acc.pay')
    <form method="POST" action="{{ route('accounts.suppliers.pay', $supplier) }}" class="bg-white rounded-xl border border-slate-200 p-4 grid gap-3 sm:grid-cols-5">
        @csrf
        <input name="amount" type="number" step="0.01" min="0.01" required placeholder="المبلغ" class="rounded-lg border border-slate-300 px-3 py-2">
        <select name="method" class="rounded-lg border border-slate-300 px-3 py-2"><option value="cash">نقدي</option><option value="bank">بنك</option><option value="check">شيك</option><option value="instapay">إنستاباي</option></select>
        <input name="ref" placeholder="مرجع" class="rounded-lg border border-slate-300 px-3 py-2">
        <input name="note" placeholder="ملاحظة" class="rounded-lg border border-slate-300 px-3 py-2">
        <button class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-bold">تسجيل دفعة</button>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="text-right px-4 py-3">التاريخ</th><th class="text-right px-4 py-3">المرجع</th><th class="text-right px-4 py-3">ملاحظة</th><th class="text-right px-4 py-3">مدين</th><th class="text-right px-4 py-3">دائن</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ledger as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['at'] }}</td>
                        <td class="px-4 py-3">{{ $row['ref'] }}</td>
                        <td class="px-4 py-3">{{ $row['note'] }}</td>
                        <td class="px-4 py-3">{{ $row['debit'] ? number_format($row['debit'],2) : '' }}</td>
                        <td class="px-4 py-3">{{ $row['credit'] ? number_format($row['credit'],2) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا توجد حركات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
