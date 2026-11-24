@extends('layouts.app')

@section('title', 'إذونات الاستلام')
@section('page-title', 'إذونات الاستلام')

@section('content')
<div class="ds-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">المورد</th>
                <th class="text-right px-4 py-3">النتيجة</th>
                <th class="text-right px-4 py-3">الفاتورة</th>
                <th class="text-right px-4 py-3">التاريخ</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($receipts as $receipt)
                <tr>
                    <td class="px-4 py-3 font-semibold"><a class="text-primary-800 font-semibold hover:text-accent-600 underline decoration-slate-300" href="{{ route('purchasing.receipts.show', $receipt) }}">{{ $receipt->no }}</a></td>
                    <td class="px-4 py-3">{{ $receipt->supplier?->name }}</td>
                    <td class="px-4 py-3">{{ $receipt->result }}</td>
                    <td class="px-4 py-3">{{ $receipt->invoice_no ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $receipt->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا توجد إيصالات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $receipts->links() }}</div>
@endsection