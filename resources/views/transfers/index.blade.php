@extends('layouts.app')

@section('title', 'التحويلات')
@section('page-title', 'التحويلات')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">التحويلات</h1>
    @can('stock.edit')
        <a href="{{ route('transfers.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">تحويل جديد</a>
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">من</th>
                <th class="text-right px-4 py-3">إلى</th>
                <th class="text-right px-4 py-3">العلب</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">التاريخ</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($transfers as $transfer)
                <tr>
                    <td class="px-4 py-3 font-semibold">#{{ $transfer->no }}</td>
                    <td class="px-4 py-3">{{ $transfer->from_loc }}</td>
                    <td class="px-4 py-3">{{ $transfer->to_loc }}</td>
                    <td class="px-4 py-3">{{ $transfer->boxes_count }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs rounded px-2 py-0.5 {{ $transfer->status === 'received' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $transfer->status === 'received' ? 'مستلم' : 'في الطريق' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $transfer->sent_at }}</td>
                    <td class="px-4 py-3"><a href="{{ route('transfers.show', $transfer) }}" class="underline text-brand-700">تفاصيل</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">لا توجد تحويلات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection