@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8"
     style="background: linear-gradient(160deg, #274b6d 0%, #1a3a58 42%, #0e2033 100%);">

    {{-- ── خلفية الصفحة الديكورية ── --}}
    <div class="pointer-events-none absolute -top-40 -left-40 h-[34rem] w-[34rem] rounded-full bg-accent-500/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-48 -right-32 h-[36rem] w-[36rem] rounded-full bg-primary-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute left-1/2 top-0 h-72 w-72 -translate-x-1/2 rounded-full bg-white/5 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.06]"
         style="background-image: radial-gradient(#ffffff 1.2px, transparent 1.2px); background-size: 24px 24px;"></div>

    {{-- ── كارت اللوجن ── --}}
    <div class="login-card relative z-10 w-full max-w-5xl overflow-hidden rounded-3xl border border-white/15 bg-white shadow-2xl shadow-black/40 ring-1 ring-black/5">
        <div class="grid lg:grid-cols-2">

            {{-- ── Brand / hero panel ── --}}
            <div class="relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex"
                 style="background: linear-gradient(155deg, #17324D 0%, #102B43 55%, #0b1a2b 100%);">
                <div class="pointer-events-none absolute -top-24 -left-24 h-64 w-64 rounded-full bg-accent-500/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-28 -right-16 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-yellow-400 to-yellow-600 text-lg font-extrabold text-[#0c1b2b] shadow-lg shadow-yellow-500/20">BR</div>
                        <div>
                            <div class="text-2xl font-extrabold tracking-wide text-white">BY RASLAN</div>
                            <div class="text-sm text-white/70">Enterprise Resource Planning</div>
                        </div>
                    </div>

                    <h1 class="mt-10 max-w-md text-3xl font-extrabold leading-snug text-white xl:text-4xl">
                        منصة موحدة لإدارة العمليات والمبيعات والمخزون
                    </h1>
                    <p class="mt-5 max-w-md text-base leading-8 text-white/80">
                        واجهة احترافية هادئة تساعد فريقك على العمل بكفاءة، مع وضوح في البيانات، وسرعة في الوصول، وتحكم كامل حسب الصلاحيات.
                    </p>

                    <div class="mt-10 grid max-w-md grid-cols-3 gap-3">
                        @foreach([['العمليات','متابعة يومية دقيقة'],['المخزون','رؤية واضحة للحركة'],['الحسابات','مؤشرات مالية موثوقة']] as [$t,$d])
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <div class="text-sm font-extrabold text-accent-200">{{ $t }}</div>
                                <div class="mt-1 text-xs leading-5 text-white/70">{{ $d }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative z-10 mt-10 text-xs text-white/60">BY RASLAN ERP — وصول آمن حسب الصلاحيات</div>
            </div>

            {{-- ── Form panel ── --}}
            <div class="p-7 sm:p-10">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-yellow-400 to-yellow-600 font-extrabold text-[#0c1b2b]">BR</div>
                    <div>
                        <div class="text-lg font-extrabold text-primary-900">BY RASLAN ERP</div>
                        <div class="text-xs text-slate-500">تسجيل الدخول للنظام</div>
                    </div>
                </div>

                <div class="mb-7">
                    <div class="text-sm font-bold text-accent-600">مرحباً بعودتك</div>
                    <h2 class="mt-1 text-2xl font-extrabold text-primary-900">تسجيل الدخول</h2>
                    <p class="mt-2 text-sm text-slate-500">أدخل بيانات حسابك للوصول إلى لوحة التحكم.</p>
                </div>

                @include('layouts.partials.flash')

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="login" class="mb-1.5 block text-sm font-semibold text-slate-700">رقم الموبايل أو البريد الإلكتروني</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autofocus required
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-primary-900 placeholder:text-slate-400 focus:border-accent-500 focus:outline-none focus:ring-4 focus:ring-accent-500/10"
                               placeholder="01xxxxxxxxx أو name@example.com">
                        @error('login')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">كلمة المرور</label>
                        <div class="relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pl-12 text-sm text-primary-900 placeholder:text-slate-400 focus:border-accent-500 focus:outline-none focus:ring-4 focus:ring-accent-500/10"
                                   placeholder="••••••••">
                            <button type="button" @click="show=!show" class="absolute inset-y-0 left-0 flex items-center px-4 text-slate-400 hover:text-primary-700" :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                        @error('password')<p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                        تذكرني على هذا الجهاز
                    </label>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-[#5a3410] bg-white px-4 py-3.5 text-sm font-extrabold text-[#5a3410] shadow-sm transition hover:bg-[#5a3410] hover:text-white focus:outline-none focus:ring-4 focus:ring-[#5a3410]/15">
                        دخول إلى النظام
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3.25h-6A2.25 2.25 0 005.25 5.5v13A2.25 2.25 0 007.5 20.75h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>

                <div class="mt-7 border-t border-slate-100 pt-5 text-center text-xs text-slate-400">BY RASLAN ERP — وصول آمن حسب الصلاحيات</div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes loginUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .login-card { animation: loginUp .5s ease-out both; }
    @media (prefers-reduced-motion: reduce) { .login-card { animation: none; } }
</style>
@endsection
