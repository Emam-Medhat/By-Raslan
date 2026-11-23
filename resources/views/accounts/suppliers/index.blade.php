@extends('layouts.app')

@section('title', 'حسابات الموردين')
@section('page-title', 'حسابات الموردين')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">المورد</th>
                <th class="text-right px-4 py-3">المشتريات</th>
                <th class="text-right px-4 py-3">المدفوع</th>
                <th class="text-right px-4 py-3">الرصيد</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($suppliers as $supplier)
                @php($b = $balances[$supplier->id])
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $supplier->name }}</td>
                    <td class="px-4 py-3">{{ number_format($b['purchases'], 2) }}</td>
                    <td class="px-4 py-3">{{ number_format($b['paid'], 2) }}</td>
                    <td class="px-4 py-3 font-semibold">{{ number_format($b['balance'], 2) }}</td>
                    <td class="px-4 py-3"><a href="{{ route('accounts.suppliers.show', $supplier) }}" class="text-brand-700 underline">كشف حساب</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا يوجد موردون</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
