@extends('layouts.app')

@section('title', 'المصروفات')
@section('page-title', 'المصروفات')

@section('content')
<div class="space-y-4">
    @can('acc.post')
        <form method="POST" action="{{ route('expenses.store') }}" class="ds-card p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @csrf
            <select name="location_id" class="ds-input">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="ds-input">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input name="amount" type="number" step="0.01" min="0" placeholder="المبلغ" class="ds-input">
            <select name="paid_from" class="ds-input">
                <option value="cash">نقدي</option>
                <option value="bank">بنك</option>
                <option value="shift">من الدرج</option>
            </select>
            <input name="note" placeholder="ملاحظة" class="ds-input">
            <button class="ds-btn ds-btn-warning" style="background: var(--warn); border-color: var(--warn); color: white;">إضافة</button>
        </form>
    @endcan

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>التاريخ</th>
                    <th>البند</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td>{{ $expense->no }}</td>
                        <td class="num" style="font-size: 12px;">{{ $expense->day }}</td>
                        <td>{{ $expense->category?->name }}</td>
                        <td class="num font-bold">{{ number_format($expense->amount, 2) }}</td>
                        <td>
                            <span class="ds-badge {{ 
                                $expense->status === 'ok' ? 'ds-badge-success' : 
                                ($expense->status === 'pending' ? 'ds-badge-warning' : 'ds-badge-danger') 
                            }}">
                                {{ ['ok' => 'مقبول', 'pending' => 'مستني', 'rejected' => 'مرفوض'][$expense->status] }}
                            </span>
                        </td>
                        <td>
                            @can('acc.approve')
                                @if ($expense->status === 'pending')
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="approve" value="1">
                                        <button class="ds-btn ds-btn-sm ds-btn-success">موافقة</button>
                                    </form>
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="approve" value="0">
                                        <button class="ds-btn ds-btn-sm ds-btn-danger" style="background: var(--bad); border-color: var(--bad); color: white;">رفض</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $expenses->links() }}</div>
</div>
@endsection