@extends('layouts.app')

@section('title', 'مرتجع جديد')
@section('page-title', 'مرتجع جديد')

@section('content')
<div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('returns.store') }}" class="space-y-4">
        @csrf
        <select name="sale_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach ($sales as $sale)
                <option value="{{ $sale->id }}">#{{ $sale->no }} — {{ $sale->total }} ج</option>
            @endforeach
        </select>
        <textarea name="reason" rows="2" placeholder="سبب الإرجاع" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
        <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">تسجيل</button>
    </form>
</div>
@endsection