@extends('layouts.app')

@section('title', 'المرتجعات')
@section('page-title', 'مرتجعات المبيعات')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">المرتجعات</h1>
    @can('pos.shift')<a href="{{ route('returns.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">مرتجع جديد</a>@endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">الفاتورة</th>
                <th class="text-right px-4 py-3">الإجمالي</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">السبب</th>
                <th class="text-right px-4 py-3">إجراء</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($returns as $return)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $return->no }}</td>
                    <td class="px-4 py-3">{{ $return->sale?->no }}</td>
                    <td class="px-4 py-3">{{ $return->total }}</td>
                    <td class="px-4 py-3">{{ $return->status }}</td>
                    <td class="px-4 py-3">{{ $return->reason ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @can('pos.shift')
                            @if ($return->status === 'pending')
                                <form method="POST" action="{{ route('returns.approve', $return) }}" class="inline">
                                    @csrf <button class="text-xs text-emerald-700 underline">موافقة</button>
                                </form>
                                <form method="POST" action="{{ route('returns.reject', $return) }}" class="inline">
                                    @csrf <button class="text-xs text-rose-600 underline">رفض</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد مرتجعات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $returns->links() }}</div>
@endsection