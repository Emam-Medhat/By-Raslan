@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="min-h-screen relative overflow-hidden flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(15,118,110,0.35),_transparent_55%),radial-gradient(ellipse_at_bottom_right,_rgba(20,184,166,0.18),_transparent_45%),linear-gradient(180deg,#020617_0%,#0f172a_45%,#020617_100%)]"></div>
    <div class="absolute inset-0 opacity-[0.07]" style="background-image:linear-gradient(rgba(255,255,255,.15) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.15) 1px,transparent 1px);background-size:42px 42px"></div>
    <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-teal-500/20 blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl"></div>

    <div class="relative w-full max-w-5xl grid lg:grid-cols-2 gap-6 items-stretch">
        <div class="hidden lg:flex flex-col justify-between rounded-3xl border border-white/10 bg-white/[0.03] backdrop-blur-sm p-8 text-white shadow-2xl">
            <div>
                <div class="inline-flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 border border-white/10">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-teal-900/40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold tracking-tight">BY RASLAN</div>
                        <div class="text-xs text-teal-200/80">Enterprise Resource Planning</div>
                    </div>
                </div>

                <h1 class="mt-10 text-4xl font-extrabold leading-tight">
                    نظام إدارة متكامل
                    <span class="block text-teal-300 mt-2">للعمليات والمخزون والمبيعات</span>
                </h1>
                <p class="mt-4 text-slate-300 leading-7 max-w-md">
                    تابع المخزون، الإنتاج، المشتريات، الطلبات، والحسابات من مكان واحد بواجهة سريعة وآمنة.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 mt-10">
                <div class="rounded-2xl bg-white/5 border border-white/10 p-4">
                    <div class="text-teal-300 font-bold text-lg">POS</div>
                    <div class="text-xs text-slate-400 mt-1">نقاط بيع وتحكم</div>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-4">
                    <div class="text-teal-300 font-bold text-lg">Stock</div>
                    <div class="text-xs text-slate-400 mt-1">مخزون وتتبع</div>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-4">
                    <div class="text-teal-300 font-bold text-lg">QA</div>
                    <div class="text-xs text-slate-400 mt-1">جودة واعتماد</div>
                </div>
            </div>
        </div>

        <div class="w-full">
            <div class="bg-white/[0.97] backdrop-blur rounded-3xl shadow-2xl shadow-black/30 border border-white/60 p-6 sm:p-8">
                <div class="lg:hidden flex items-center gap-3 mb-7">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                    </div>
                    <div>
                        <div class="text-lg font-extrabold text-slate-900">BY RASLAN ERP</div>
                        <div class="text-xs text-slate-500">نظام إدارة متكامل</div>
                    </div>
                </div>

                <div class="mb-7">
                    <p class="text-sm font-semibold text-teal-700">مرحبًا بعودتك</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">تسجيل الدخول</h2>
                    <p class="text-sm text-slate-500 mt-2">ادخل بياناتك للوصول إلى لوحة التحكم.</p>
                </div>

                @include('layouts.partials.flash')

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-semibold text-slate-700 mb-1.5">الموبايل أو الإيميل</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z"/></svg>
                            </span>
                            <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autofocus required
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pr-11 pl-4 py-3 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition"
                                   placeholder="01xxxxxxxxx أو name@example.com">
                        </div>
                        @error('login')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">الرقم السري</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            </span>
                            <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pr-11 pl-12 py-3 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition"
                                   placeholder="••••••••">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 left-0 px-3.5 text-slate-400 hover:text-slate-600 transition" :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3 text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" name="remember" @checked(old('remember')) class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30">
                            <span>تذكّرني</span>
                        </label>
                        <span class="text-slate-400">دخول آمن للنظام</span>
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-bold py-3.5 shadow-lg shadow-teal-700/20 focus:outline-none focus:ring-4 focus:ring-teal-500/20 transition">
                        <span>دخول للنظام</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3.25h-6A2.25 2.25 0 005.25 5.5v13A2.25 2.25 0 007.5 20.75h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>

                <div class="mt-7 pt-5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>BY RASLAN ERP</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>النظام جاهز</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
