@extends('layouts.app')

@section('title', 'استلام وتحصيل الأوردرات')
@section('page-title', 'استلام وتحصيل الأوردرات')

@section('content')
<div class="space-y-4">
    @if(session('status'))<div class="ds-alert ds-alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="ds-alert ds-alert-danger">{{ $errors->first() }}</div>@endif

    <div class="ds-card p-4 flex gap-2 flex-wrap">
        <a href="{{ route('orders.collections') }}" class="ds-btn ds-btn-sm {{ $status===''?'ds-btn-primary':'ds-btn-secondary' }}">الكل</a>
        <a href="{{ route('orders.collections',['status'=>'pending_collection']) }}" class="ds-btn ds-btn-sm {{ $status==='pending_collection'?'ds-btn-primary':'ds-btn-secondary' }}">بانتظار التحصيل</a>
        <a href="{{ route('orders.collections',['status'=>'collected']) }}" class="ds-btn ds-btn-sm {{ $status==='collected'?'ds-btn-primary':'ds-btn-secondary' }}">تم التحصيل</a>
    </div>

    <div class="ds-card overflow-x-auto">
        <table class="ds-table">
            <thead><tr><th>الأوردر</th><th>العميل</th><th>الفرع</th><th>الحالة</th><th>الإجمالي</th><th>التحصيل</th><th>إجراء</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td>#{{ $o->no }}</td>
                    <td>{{ $o->customer?->name }}<div class="text-xs text-slate-500">{{ $o->customer?->phone }}</div></td>
                    <td>{{ $o->location?->name ?? $o->location_id }}</td>
                    <td><span class="ds-badge ds-badge-neutral">{{ $o->status }}</span></td>
                    <td class="num">{{ number_format((float)($o->total ?? $o->est_total),2) }}</td>
                    <td>
                        @if($o->collected_at)
                            <div class="text-xs text-emerald-700">تم: {{ $o->collection_method }} — {{ number_format((float)$o->collected_amount,2) }}</div>
                        @else
                            <span class="ds-badge ds-badge-warning">غير محصل</span>
                        @endif
                    </td>
                    <td>
                        @if(!$o->collected_at)
                        <form method="POST" action="{{ route('orders.collect', $o) }}" class="flex gap-1 items-center flex-wrap">
                            @csrf
                            <label class="text-xs"><input type="checkbox" name="delivered" value="1" checked> تم التسليم</label>
                            <select name="method" class="ds-input !h-9 !py-1 w-28">
                                <option value="cash">Cash</option>
                                <option value="instapay">InstaPay</option>
                                <option value="visa">Visa</option>
                                <option value="wallet">Wallet</option>
                            </select>
                            <input name="amount" type="number" step="0.01" min="0" value="{{ (float)($o->total ?? $o->est_total) }}" class="ds-input !h-9 !py-1 w-28" dir="ltr">
                            <button class="ds-btn ds-btn-sm ds-btn-primary">تسجيل التحصيل</button>
                        </form>
                        @else
                            <span class="text-xs text-slate-500">{{ $o->collected_at?->format('Y-m-d H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="ds-empty">لا توجد أوردرات</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $orders->links() }}</div>
</div>
@endsection
