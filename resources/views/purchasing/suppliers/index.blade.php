@extends('layouts.app')

@section('title', 'الموردين')
@section('page-title', 'الموردين')

@section('content')
<div class="space-y-4" x-data="{ show: false }">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">الموردون</h1>
        @can('purchase.edit')
            <button @click="show = !show" class="ds-btn ds-btn-primary rounded-lg px-4 py-2 text-sm font-bold">إضافة مورد</button>
        @endcan
    </div>

    <form x-show="show" x-cloak method="POST" action="{{ route('purchasing.suppliers.store') }}" class="ds-card p-5 grid gap-3 sm:grid-cols-3">
        @csrf
        <input name="name" placeholder="الاسم" class="ds-input">
        <input name="phone" placeholder="الموبايل" class="ds-input">
        <input name="terms" placeholder="الشروط" class="ds-input">
        <button class="ds-btn ds-btn-accent">حفظ</button>
    </form>

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">المورد</th>
                    <th class="text-right px-4 py-3">المشتريات</th>
                    <th class="text-right px-4 py-3">المدفوع</th>
                    <th class="text-right px-4 py-3">الرصيد</th>
                    <th class="text-right px-4 py-3">إجراءات</th>
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
                        <td class="px-4 py-3">
                            @can('acc.pay')
                                <form method="POST" action="{{ route('purchasing.suppliers.pay', $supplier) }}" class="inline-flex gap-1 items-center">
                                    @csrf
                                    <input name="amount" type="number" step="0.01" placeholder="مبلغ" class="w-20 rounded border border-slate-300 px-2 py-1">
                                    <select name="method" class="rounded border border-slate-300 px-1 py-1">
                                        <option value="cash">نقدي</option>
                                        <option value="bank">بنك</option>
                                    </select>
                                    <button class="text-xs text-emerald-700 underline">دفع</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا يوجد موردون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection