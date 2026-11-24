@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="min-h-screen bg-[linear-gradient(135deg,#17324d_0%,#1f4463_45%,#f4f7fa_45%,#f4f7fa_100%)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-6xl items-center gap-8 lg:grid-cols-2">
        <div class="hidden text-white lg:block">
            <div class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/5 px-4 py-3 backdrop-blur">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent-500 text-lg font-extrabold">BR</div>
                <div>
                    <div class="text-2xl font-extrabold tracking-wide">BY RASLAN</div>
                    <div class="text-sm text-white/65">Enterprise Resource Planning</div>
                </div>
            </div>
            <h1 class="mt-10 max-w-xl text-4xl font-extrabold leading-tight xl:text-5xl">منصة موحدة لإدارة العمليات والمبيعات والمخزون</h1>
            <p class="mt-5 max-w-lg text-base leading-8 text-white/75">واجهة احترافية هادئة تساعد فريقك على العمل بكفاءة، مع وضوح في البيانات، وسرعة في الوصول، وتحكم كامل حسب الصلاحيات.</p>
            <div class="mt-10 grid max-w-lg grid-cols-3 gap-3">
                @foreach([['العمليات','متابعة يومية دقيقة'],['المخزون','رؤية واضحة للحركة'],['الحسابات','مؤشرات مالية موثوقة']] as [$t,$d])
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="text-sm font-extrabold text-accent-300">{{ $t }}</div>
                    <div class="mt-1 text-xs leading-5 text-white/60">{{ $d }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="w-full">
            <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-7 shadow-[0_1px_2px_rgba(23,50,77,.04),0_20px_50px_rgba(23,50,77,.10)] sm:p-8">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-800 font-extrabold text-white">BR</div>
                    <div>
                        <div class="text-lg font-extrabold text-primary-800">BY RASLAN ERP</div>
                        <div class="text-xs text-slate-400">تسجيل الدخول للنظام</div>
                    </div>
                </div>

                <div class="mb-7">
                    <div class="text-sm font-bold text-accent-600">مرحباً بعودتك</div>
                    <h2 class="mt-1 text-2xl font-extrabold text-primary-800">تسجيل الدخول</h2>
                    <p class="mt-2 text-sm text-slate-500">أدخل بيانات حسابك للوصول إلى لوحة التحكم.</p>
                </div>

                @include('layouts.partials.flash')

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="login" class="mb-1.5 block text-sm font-semibold text-slate-700">رقم الموبايل أو البريد الإلكتروني</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autofocus required
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-primary-800 placeholder:text-slate-400 focus:border-accent-500 focus:outline-none focus:ring-4 focus:ring-accent-500/10"
                               placeholder="01xxxxxxxxx أو name@example.com">
                        @error('login')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{show:false}">
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">كلمة المرور</label>
                        <div class="relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pl-12 text-sm text-primary-800 placeholder:text-slate-400 focus:border-accent-500 focus:outline-none focus:ring-4 focus:ring-accent-500/10"
                                   placeholder="••••••••">
                            <button type="button" @click="show=!show" class="absolute inset-y-0 left-0 px-4 text-slate-400 hover:text-primary-700" :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                        @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                        تذكرني على هذا الجهاز
                    </label>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-800 px-4 py-3.5 text-sm font-extrabold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-accent-500/15">
                        دخول إلى النظام
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3.25h-6A2.25 2.25 0 005.25 5.5v13A2.25 2.25 0 007.5 20.75h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-xs text-slate-400">BY RASLAN ERP — وصول آمن حسب الصلاحيات</div>
            </div>
        </div>
    </div>
</div>
@endsection
