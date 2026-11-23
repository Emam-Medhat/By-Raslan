@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-extrabold text-brand-700">BY RASLAN ERP</h1>
            <p class="text-sm text-slate-500 mt-1">سجّل الدخول للمتابعة</p>
        </div>

        @include('layouts.partials.flash')

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1">الموبايل أو الإيميل</label>
                <input name="login" value="{{ old('login') }}" autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-600 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الرقم السري</label>
                <input type="password" name="password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-brand-600 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded"> تذكّرني
            </label>
            <button class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg py-2.5">
                دخول
            </button>
        </form>
    </div>
</div>
@endsection