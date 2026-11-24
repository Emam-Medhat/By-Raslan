@extends('layouts.app')

@section('title', 'مرتجعات الموردين')
@section('page-title', 'مرتجعات الموردين')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">مرتجعات الموردين</h1>
    @can('purchase.edit')
        <a href="{{ route('purchase-returns.create') }}" class="ds-btn ds-btn-primary rounded-lg px-4 py-2 text-sm font-bold">مرتجع مورد</a>
    @endcan
</div>

<div class="ds-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">المورد</th>
                <th class="text-right px-4 py-3">الإجمالي</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">إجراء</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($returns as $return)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $return->no }}</td>
                    <td class="px-4 py-3">{{ $return->supplier?->name }}</td>
                    <td class="px-4 py-3">{{ $return->total }}</td>
                    <td class="px-4 py-3">{{ $return->status }}</td>
                    <td class="px-4 py-3">
                        @can('purchase.edit')
                            @if ($return->status === 'pending')
                                <form method="POST" action="{{ route('purchase-returns.approve', $return) }}" class="inline">
                                    @csrf <button class="text-xs text-emerald-700 underline">موافقة</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا توجد مرتجعات موردين</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $returns->links() }}</div>
@endsection