@extends('layouts.app')

@section('title', 'فتح وردية جديدة')
@section('page-title', 'فتح وردية جديدة')

@section('content')
<div class="max-w-2xl bg-white border border-slate-200 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">فتح وردية جديدة</h1>
        <a href="{{ route('shifts.index') }}" class="ds-btn ds-btn-secondary rounded-lg px-4 py-2 text-sm font-bold">← عودة</a>
    </div>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 mb-4">
            <ul class="list-disc list-inside text-sm text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('shifts.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-1">الفرع <span class="text-red-500">*</span></label>
            <select name="location_id" class="w-full ds-input" required>
                <option value="">اختر الفرع</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected(old('location_id') === $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">الموظف <span class="text-red-500">*</span></label>
            <select name="cashier" class="w-full ds-input" required>
                <option value="">اختر الموظف</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('cashier') === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">رصيد البداية <span class="text-red-500">*</span></label>
            <input name="opening_cash" type="number" step="0.01" min="0" value="{{ old('opening_cash', 0) }}" class="w-full ds-input" required>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2.5 font-bold">فتح الوردية</button>
            <a href="{{ route('shifts.index') }}" class="ds-btn ds-btn-secondary rounded-lg px-5 py-2.5 font-bold">إلغاء</a>
        </div>
    </form>
</div>
@endsection