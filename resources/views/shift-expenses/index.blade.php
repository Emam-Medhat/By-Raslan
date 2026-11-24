@extends('layouts.app')

@section('title', 'مصروفات الورديات')
@section('page-title', 'مصروفات الورديات')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">مصروفات الورديات</h1>
        <a href="{{ route('shifts.index') }}" class="ds-btn ds-btn-secondary ds-btn-sm">→ الورديات</a>
    </div>

    <form method="GET" class="ds-card p-4 grid gap-3 sm:grid-cols-3">
        <select name="status" class="ds-input">
            <option value="">كل الحالات</option>
            <option value="pending" @selected(request('status') === 'pending')>قيد المراجعة</option>
            <option value="approved" @selected(request('status') === 'approved')>معتمد</option>
            <option value="rejected" @selected(request('status') === 'rejected')>مرفوض</option>
        </select>
        <button class="ds-btn ds-btn-primary">تصفية</button>
        <a href="{{ route('shift-expenses.index') }}" class="ds-btn ds-btn-secondary">إعادة ضبط</a>
    </form>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>البند</th>
                    <th>المبلغ</th>
                    <th>الوردية</th>
                    <th>الموظف</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="font-semibold">#{{ $expense->id }}</td>
                        <td>{{ $expense->title }}</td>
                        <td class="num font-bold">{{ number_format((float) $expense->amount, 2) }}</td>
                        <td><a href="{{ route('shifts.show', $expense->shift) }}" class="ds-link">#{{ $expense->shift_id }}</a></td>
                        <td>{{ $expense->user?->name }}</td>
                        <td>
                            <span class="ds-badge {{ 
                                $expense->status === 'approved' ? 'ds-badge-success' : 
                                ($expense->status === 'pending' ? 'ds-badge-warning' : 'ds-badge-danger') 
                            }}">
                                {{ $expense->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="num" style="font-size: 12px;">{{ $expense->expense_date?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @if($expense->status === 'pending')
                                <form method="POST" action="{{ route('shift-expenses.approve', $expense) }}" class="inline">
                                    @csrf
                                    <button class="ds-btn ds-btn-sm ds-btn-success">اعتماد</button>
                                </form>
                                <form method="POST" action="{{ route('shift-expenses.reject', $expense) }}" class="inline">
                                    @csrf
                                    <button class="ds-btn ds-btn-sm ds-btn-danger" style="background: var(--bad); border-color: var(--bad); color: white;">رفض</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="ds-empty">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $expenses->links() }}</div>
</div>
@endsection