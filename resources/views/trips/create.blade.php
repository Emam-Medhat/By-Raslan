@extends('layouts.app')

@section('title', 'رحلة جديدة')
@section('page-title', 'رحلة جديدة')

@section('content')
<div class="max-w-lg bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="{{ route('trips.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1">العربية</label>
            <input name="truck" class="w-full ds-input">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">السائق</label>
            <input name="driver" class="w-full ds-input">
        </div>
        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">إنشاء</button>
    </form>
</div>
@endsection