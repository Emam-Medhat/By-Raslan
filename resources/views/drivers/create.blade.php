@extends('layouts.app')

@section('title', 'إضافة طيار')
@section('page-title', 'إضافة طيار')

@section('content')
<div class="ds-card p-5 max-w-xl">
    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('drivers.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="ds-label">اسم الطيار *</label>
            <input name="name" value="{{ old('name') }}" required class="ds-input">
        </div>
        <div>
            <label class="ds-label">الموبايل</label>
            <input name="phone" value="{{ old('phone') }}" class="ds-input" dir="ltr">
        </div>
        <div>
            <label class="ds-label">الفرع *</label>
            <select name="location_id" required class="ds-input">
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>{{ $loc->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">الطيار بيظهر بس في طلبات الفرع ده عند الخروج للتوصيل.</p>
        </div>
        <div>
            <label class="ds-label">حساب موظف (اختياري)</label>
            <select name="user_id" class="ds-input">
                <option value="">— بدون حساب —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} ({{ $u->phone }})</option>
                @endforeach
            </select>
        </div>
        <button class="ds-btn ds-btn-primary rounded px-4 py-2 font-bold">حفظ الطيار</button>
    </form>
</div>
@endsection
