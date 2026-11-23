@extends('layouts.app')

@section('title', 'أمر إنتاج')
@section('page-title', 'أمر إنتاج جديد')

@section('content')
<div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('production.store') }}" class="space-y-4">
        @csrf
        <select name="item_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <input name="qty" type="number" step="0.001" placeholder="الكمية" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <input name="due" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <input name="note" placeholder="ملاحظة" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">إنشاء</button>
    </form>
</div>
@endsection