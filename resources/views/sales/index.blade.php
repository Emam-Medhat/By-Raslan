@extends('layouts.app')

@section('title', 'المبيعات')
@section('page-title', 'المبيعات والفواتير')

@section('content')
<div class="flex justify-end mb-3">
    @can('acc.view')
        <a href="{{ route('sales.export') }}" class="text-sm bg-white border border-slate-300 rounded-lg px-3 py-2 hover:bg-slate-50">⬇ تصدير CSV</a>
    @endcan
</div>
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">الكاشير</th>
                <th class="text-right px-4 py-3">الإجمالي</th>
                <th class="text-right px-4 py-3">نقدي</th>
                <th class="text-right px-4 py-3">فيزا</th>
                <th class="text-right px-4 py-3">الباقي</th>
                <th class="text-right px-4 py-3">التاريخ</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($sales as $sale)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $sale->no }}</td>
                    <td class="px-4 py-3">{{ $sale->cashier?->name }}</td>
                    <td class="px-4 py-3">{{ $sale->total }}</td>
                    <td class="px-4 py-3">{{ $sale->paid_cash }}</td>
                    <td class="px-4 py-3">{{ $sale->paid_card }}</td>
                    <td class="px-4 py-3">{{ $sale->change }}</td>
                    <td class="px-4 py-3">{{ $sale->at }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">لا توجد مبيعات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $sales->links() }}</div>
@endsection