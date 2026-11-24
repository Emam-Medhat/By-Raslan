@extends('layouts.app')

@section('title', 'التحويلات النقدية')
@section('page-title', 'التحويلات النقدية')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">التحويلات النقدية</h1>
        <a href="{{ route('shifts.index') }}" class="ds-btn ds-btn-secondary ds-btn-sm">→ الورديات</a>
    </div>

    <form method="GET" class="ds-card p-4 grid gap-3 sm:grid-cols-4">
        <select name="type" class="ds-input">
            <option value="">كل الأنواع</option>
            @foreach(\App\Modules\Sales\Models\CashTransfer::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="ds-input">
            <option value="">كل الحالات</option>
            <option value="pending" @selected(request('status') === 'pending')>قيد المراجعة</option>
            <option value="approved" @selected(request('status') === 'approved')>معتمد</option>
            <option value="rejected" @selected(request('status') === 'rejected')>مرفوض</option>
        </select>
        <button class="ds-btn ds-btn-primary">تصفية</button>
        <a href="{{ route('cash-transfers.index') }}" class="ds-btn ds-btn-secondary">إعادة ضبط</a>
    </form>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>من وردية</th>
                    <th>إلى</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transfers as $transfer)
                    <tr>
                        <td class="font-semibold">#{{ $transfer->id }}</td>
                        <td>{{ $transfer->getTypeLabel() }}</td>
                        <td class="num font-bold">{{ number_format((float) $transfer->amount, 2) }}</td>
                        <td><a href="{{ route('shifts.show', $transfer->fromShift) }}" class="ds-link">#{{ $transfer->from_shift_id }}</a></td>
                        <td>{{ $transfer->toUser?->name ?? '—' }}</td>
                        <td>
                            <span class="ds-badge {{ 
                                $transfer->status === 'approved' ? 'ds-badge-success' : 
                                ($transfer->status === 'pending' ? 'ds-badge-warning' : 'ds-badge-danger') 
                            }}">
                                {{ $transfer->getStatusLabel() }}
                            </span>
                        </td>
                        <td>
                            @if($transfer->status === 'pending')
                                <form method="POST" action="{{ route('cash-transfers.approve', $transfer) }}" class="inline">
                                    @csrf
                                    <button class="ds-btn ds-btn-sm ds-btn-success">اعتماد</button>
                                </form>
                                <form method="POST" action="{{ route('cash-transfers.reject', $transfer) }}" class="inline">
                                    @csrf
                                    <button class="ds-btn ds-btn-sm ds-btn-danger" style="background: var(--bad); border-color: var(--bad); color: white;">رفض</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ds-empty">لا توجد تحويلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $transfers->links() }}</div>
</div>
@endsection