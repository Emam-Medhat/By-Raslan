@extends('layouts.app')

@section('title', 'قريباً')
@section('page-title', 'قريباً')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-10 text-center max-w-xl">
    <h1 class="text-xl font-bold mb-2">{{ request('m', 'الشاشة دي') }} — قيد الإعداد</h1>
    <p class="text-slate-500 mb-4">الشاشة دي لسه بتتبني.</p>
    <a href="{{ request('b') ? route('branches.show', request('b')) : route('dashboard') }}" class="inline-block rounded-lg border border-slate-200 px-4 py-2">رجوع</a>
</div>
@endsection
