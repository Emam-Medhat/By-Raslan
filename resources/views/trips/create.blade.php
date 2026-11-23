@extends('layouts.app')

@section('title', 'رحلة جديدة')
@section('page-title', 'رحلة جديدة')

@section('content')
<div class="max-w-lg bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('trips.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-1">العربية</label>
            <input name="truck" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">السائق</label>
            <input name="driver" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">إنشاء</button>
    </form>
</div>
@endsection