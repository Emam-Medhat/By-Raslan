@extends('layouts.app')

@section('title', 'إضافة مستخدم')
@section('page-title', 'إضافة مستخدم')
@section('page-subtitle', 'إنشاء حساب جديد وتحديد الدور والصلاحيات المباشرة')

@section('page-actions')
    <a href="{{ route('users.index') }}" class="ds-btn ds-btn-secondary">
        <x-icon name="arrow-right" size="16" />
        رجوع للمستخدمين
    </a>
@endsection

@section('content')
@php
    $grouped = $permissions->groupBy('grp');
@endphp

<div class="w-full">
    <form method="POST" action="{{ route('users.store') }}" class="space-y-5" x-data="{ showPassword: false, selected: {{ count(old('permissions', [])) }} }">
        @csrf

        <div class="grid gap-5 xl:grid-cols-12">
            <div class="xl:col-span-8 space-y-5">
                <div class="ds-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 text-accent-600">
                                <x-icon name="user-plus" size="20" />
                            </span>
                            <div>
                                <h2 class="text-base font-extrabold text-primary-800">بيانات الحساب</h2>
                                <p class="text-xs text-slate-500 mt-0.5">المعلومات الأساسية المطلوبة لتسجيل الدخول.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="ds-label">الاسم <span class="text-red-500">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" class="ds-input @error('name') !border-red-400 @enderror" required autocomplete="name" placeholder="اسم المستخدم">
                            @error('name') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="ds-label">رقم الموبايل <span class="text-red-500">*</span></label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" class="ds-input @error('phone') !border-red-400 @enderror" dir="ltr" required autocomplete="tel" placeholder="01xxxxxxxxx">
                            @error('phone') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="ds-label">البريد الإلكتروني <span class="text-slate-400 font-normal">(اختياري)</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="ds-input @error('email') !border-red-400 @enderror" dir="ltr" autocomplete="email" placeholder="name@example.com">
                            @error('email') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="ds-label">كلمة المرور <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" class="ds-input pl-10 @error('password') !border-red-400 @enderror" dir="ltr" required autocomplete="new-password" placeholder="8 أحرف على الأقل">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 left-0 px-3 text-slate-400 hover:text-primary-800" :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                    <span x-show="!showPassword"><x-icon name="eye" size="16" /></span>
                                    <span x-show="showPassword" x-cloak><x-icon name="eye-off" size="16" /></span>
                                </button>
                            </div>
                            @error('password') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" @checked(old('active', '1') == '1') class="h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                                تفعيل الحساب فور الإنشاء
                            </label>
                        </div>
                    </div>
                </div>

                <div class="ds-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                                <x-icon name="shield-check" size="20" />
                            </span>
                            <div>
                                <h2 class="text-base font-extrabold text-primary-800">الصلاحيات المباشرة</h2>
                                <p class="text-xs text-slate-500 mt-0.5">اختياري — يمكنك منح صلاحيات إضافية بجانب الدور.</p>
                            </div>
                        </div>
                        <span class="ds-badge ds-badge-neutral" x-text="selected + ' محدد'"></span>
                    </div>

                    <div class="p-5 space-y-4">
                        @forelse($grouped as $group => $perms)
                            <div>
                                <div class="flex items-center gap-2 mb-2.5">
                                    <span class="text-xs font-extrabold tracking-wide text-slate-500 uppercase">{{ $group ?: 'عام' }}</span>
                                    <span class="flex-1 h-px bg-slate-100"></span>
                                    <span class="text-[11px] text-slate-400">{{ $perms->count() }}</span>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach($perms as $perm)
                                        <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50/80 hover:border-slate-300 transition cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                @checked(in_array($perm->id, old('permissions', [])))
                                                @change="selected = $el.closest('form').querySelectorAll('input[name=\'permissions[]\']:checked').length"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-primary-800 leading-snug">{{ $perm->label }}</span>
                                                <span class="block text-[11px] text-slate-400 font-mono" dir="ltr">{{ $perm->id }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">لا توجد صلاحيات متاحة.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 space-y-5 xl:sticky xl:top-24 self-start">
                <div class="ds-card p-5 space-y-4">
                    <h3 class="text-sm font-bold text-primary-800 flex items-center gap-2">
                        <x-icon name="key-round" size="16" class="text-slate-400" />
                        الدور والوصول
                    </h3>

                    <div>
                        <label for="role_id" class="ds-label">الدور <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <select id="role_id" name="role_id" class="ds-select @error('role_id') !border-red-400 @enderror">
                            <option value="">— بدون دور —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <p class="ds-error">{{ $message }}</p> @enderror
                        <p class="ds-help">الدور يمنح مجموعة صلاحيات جاهزة. يمكن إضافة صلاحيات مباشرة أدناه.</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs text-slate-500 space-y-1.5">
                        <div class="flex gap-2"><x-icon name="info" size="14" class="text-slate-400 shrink-0 mt-0.5" /><span>لا يمكن منح صلاحية لا تملكها أنت (anti-escalation).</span></div>
                        <div class="flex gap-2"><x-icon name="info" size="14" class="text-slate-400 shrink-0 mt-0.5" /><span>رقم الموبايل وكلمة المرور مطلوبان لتسجيل الدخول.</span></div>
                    </div>
                </div>

                <div class="ds-card p-5">
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="ds-btn ds-btn-primary w-full">
                            <x-icon name="save" size="16" />
                            حفظ المستخدم
                        </button>
                        <a href="{{ route('users.index') }}" class="ds-btn ds-btn-secondary w-full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
