@extends('layouts.app')

@section('title', 'جرد جديد')
@section('page-title', 'جرد جديد')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">جرد جديد</h1>
        <a href="{{ route('stock-counts.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة</a>
    </div>

    <form method="POST" action="{{ route('stock-counts.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1">الفرع</label>
            <select name="location_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" required>
                <option value="">اختر الفرع</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected(old('location_id') === $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="أي ملاحظات...">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button class="bg-accent-500 hover:bg-brand-700 text-white rounded-lg px-5 py-2.5 font-bold">إنشاء الجرد</button>
            <a href="{{ route('stock-counts.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2.5">إلغاء</a>
        </div>
    </form>
</div>
@endsection