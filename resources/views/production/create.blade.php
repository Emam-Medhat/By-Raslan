@extends('layouts.app')

@section('title', 'أمر إنتاج')
@section('page-title', 'أمر إنتاج جديد')

@section('content')
<div class="max-w-lg bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="{{ route('production.store') }}" class="space-y-4">
        @csrf
        <select name="item_id" class="w-full ds-input">
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <input name="qty" type="number" step="0.001" placeholder="الكمية" class="w-full ds-input">
        <input name="due" type="date" class="w-full ds-input">
        <input name="note" placeholder="ملاحظة" class="w-full ds-input">
        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">إنشاء</button>
    </form>
</div>
@endsection