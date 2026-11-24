@extends('layouts.app')

@section('title', 'تحويل نقدي من الوردية #'.$shift->id)
@section('page-title', 'تحويل نقدي من الوردية #'.$shift->id)

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">تحويل نقدي من الوردية #{{ $shift->id }}</h1>
        <a href="{{ route('shifts.show', $shift) }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
    </div>

    <form method="POST" action="{{ route('cash-transfers.store', $shift) }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1">نوع التحويل</label>
            <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" required>
                @foreach(\App\Modules\Sales\Models\CashTransfer::TYPES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">المبلغ (ج.م)</label>
            <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" required>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">إلى (اختياري)</label>
            <select name="to_user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                <option value="">بدون تحديد</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button class="bg-accent-500 hover:bg-brand-700 text-white rounded-lg px-5 py-2.5 font-bold">تسجيل التحويل</button>
            <a href="{{ route('shifts.show', $shift) }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2.5">إلغاء</a>
        </div>
    </form>
</div>
@endsection