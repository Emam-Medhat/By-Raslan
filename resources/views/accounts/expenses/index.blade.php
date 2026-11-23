@extends('layouts.app')

@section('title', 'المصروفات')
@section('page-title', 'المصروفات')

@section('content')
<div class="space-y-4">
    @can('acc.post')
        <form method="POST" action="{{ route('expenses.store') }}" class="bg-white rounded-xl border border-slate-200 p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @csrf
            <select name="location_id" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input name="amount" type="number" step="0.01" min="0" placeholder="المبلغ" class="rounded-lg border border-slate-300 px-3 py-2">
            <select name="paid_from" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="cash">نقدي</option>
                <option value="bank">بنك</option>
                <option value="shift">من الدرج</option>
            </select>
            <input name="note" placeholder="ملاحظة" class="rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-amber-600 text-white rounded-lg px-4 py-2 font-bold">إضافة</button>
        </form>
    @endcan

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">رقم</th>
                    <th class="text-right px-4 py-3">التاريخ</th>
                    <th class="text-right px-4 py-3">البند</th>
                    <th class="text-right px-4 py-3">المبلغ</th>
                    <th class="text-right px-4 py-3">الحالة</th>
                    <th class="text-right px-4 py-3">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-4 py-3">{{ $expense->no }}</td>
                        <td class="px-4 py-3">{{ $expense->day }}</td>
                        <td class="px-4 py-3">{{ $expense->category?->name }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $expense->amount }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs rounded px-2 py-0.5 {{ $expense->status === 'ok' ? 'bg-emerald-100 text-emerald-700' : ($expense->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                {{ ['ok' => 'مقبول', 'pending' => 'مستني', 'rejected' => 'مرفوض'][$expense->status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @can('acc.approve')
                                @if ($expense->status === 'pending')
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="approve" value="1">
                                        <button class="text-xs text-emerald-700 underline">موافقة</button>
                                    </form>
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="approve" value="0">
                                        <button class="text-xs text-rose-600 underline">رفض</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $expenses->links() }}</div>
</div>
@endsection