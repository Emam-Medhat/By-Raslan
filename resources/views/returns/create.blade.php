@extends('layouts.app')

@section('title', 'مرتجع جديد')
@section('page-title', 'مرتجع جديد')

@section('content')
<div class="max-w-lg bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="{{ route('returns.store') }}" class="space-y-4">
        @csrf
        <select name="sale_id" class="w-full ds-input">
            @foreach ($sales as $sale)
                <option value="{{ $sale->id }}">#{{ $sale->no }} — {{ $sale->total }} ج</option>
            @endforeach
        </select>
        <textarea name="reason" rows="2" placeholder="سبب الإرجاع" class="w-full ds-input"></textarea>
        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">تسجيل</button>
    </form>
</div>
@endsection