@extends('layouts.app')

@section('title', 'إضافة مصروف للوردية #'.$shift->id)
@section('page-title', 'إضافة مصروف للوردية #'.$shift->id)

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">إضافة مصروف للوردية #{{ $shift->id }}</h1>
        <a href="{{ route('shifts.show', $shift) }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
    </div>

    <form method="POST" action="{{ route('shift-expenses.store', $shift) }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1">البند</label>
            <input name="title" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" required>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">المبلغ (ج.م)</label>
            <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" required>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">التاريخ</label>
            <input name="expense_date" type="datetime-local" value="{{ old('expense_date', now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button class="bg-accent-500 hover:bg-brand-700 text-white rounded-lg px-5 py-2.5 font-bold">حفظ</button>
            <a href="{{ route('shifts.show', $shift) }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2.5">إلغاء</a>
        </div>
    </form>
</div>
@endsection