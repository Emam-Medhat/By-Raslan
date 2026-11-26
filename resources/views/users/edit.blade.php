@extends('layouts.app')

@section('title', 'تعديل مستخدم')
@section('page-title', 'تعديل المستخدم')
@section('page-subtitle', $user->name)

@section('page-actions')
    <a href="{{ route('users.index') }}" class="ds-btn ds-btn-secondary"><x-icon name="arrow-right" size="16" /> رجوع للمستخدمين</a>
@endsection

@section('content')
@php
    $groups = $accessGroups ?? \App\Modules\Identity\Support\AccessRegistry::groups();
    $navIds = \App\Modules\Identity\Support\AccessRegistry::permissionIds();
    $currentNav = old('nav_permissions', $user->permissions->pluck('id')->intersect($navIds)->values()->all());
    $currentRoleId = old('role_id', $user->roles->first()?->id);
@endphp

<div class="w-full" x-data="userAccessForm(@js($currentNav))">
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-5 xl:grid-cols-12">
            <div class="xl:col-span-8 space-y-5">
                <div class="ds-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 text-accent-600"><x-icon name="user-round-cog" size="20" /></span>
                        <div>
                            <h2 class="text-base font-extrabold text-primary-800">بيانات الحساب</h2>
                            <p class="text-xs text-slate-500 mt-0.5">تحديث بيانات الدخول والحالة.</p>
                        </div>
                    </div>
                    <div class="p-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ds-label">الاسم <span class="text-red-500">*</span></label>
                            <input name="name" value="{{ old('name', $user->name) }}" class="ds-input @error('name') !border-red-400 @enderror" required>
                            @error('name') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="ds-label">رقم الموبايل <span class="text-red-500">*</span></label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" class="ds-input @error('phone') !border-red-400 @enderror" dir="ltr" required>
                            @error('phone') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="ds-label">البريد الإلكتروني</label>
                            <input name="email" type="email" value="{{ old('email', $user->email) }}" class="ds-input @error('email') !border-red-400 @enderror" dir="ltr">
                            @error('email') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="ds-label">كلمة مرور جديدة <span class="text-slate-400 font-normal">(اتركها فارغة للإبقاء)</span></label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" class="ds-input pl-10 @error('password') !border-red-400 @enderror" dir="ltr">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 left-0 px-3 text-slate-400 hover:text-primary-800">
                                    <span x-show="!showPassword"><x-icon name="eye" size="16" /></span>
                                    <span x-show="showPassword" x-cloak><x-icon name="eye-off" size="16" /></span>
                                </button>
                            </div>
                            @error('password') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="ds-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><x-icon name="layout-list" size="20" /></span>
                            <div>
                                <h2 class="text-base font-extrabold text-primary-800">صفحات السايدبار المسموح بها</h2>
                                <p class="text-xs text-slate-500 mt-0.5">اختار اللينكات اللي المستخدم هيشوفها ويقدر يزورها. أي لينك مش مختار هيكون مخفي ومحمي.</p>
                            </div>
                        </div>
                        <span class="ds-badge ds-badge-neutral" x-text="selected.length + ' صفحة'"></span>
                    </div>
                    <div class="p-5 space-y-5">
                        @foreach($groups as $group)
                            <div>
                                <div class="flex items-center gap-2 mb-2.5">
                                    <span class="text-xs font-extrabold tracking-wide text-slate-500">{{ $group['label'] }}</span>
                                    <span class="flex-1 h-px bg-slate-100"></span>
                                    <button type="button" class="text-[11px] text-accent-600 hover:underline" @click="toggleGroup(@js(collect($group['links'])->pluck('permission')->all()))">تحديد/إلغاء المجموعة</button>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach($group['links'] as $link)
                                        <label class="flex items-start gap-2.5 rounded-xl border px-3 py-2.5 cursor-pointer transition"
                                               :class="selected.includes(@js($link['permission'])) ? 'border-accent-300 bg-accent-50/40' : 'border-slate-200 hover:bg-slate-50/80'">
                                            <input type="checkbox" name="nav_permissions[]" value="{{ $link['permission'] }}" x-model="selected" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                                            <span class="min-w-0 flex items-center gap-2">
                                                <x-icon name="{{ $link['icon'] }}" size="15" class="text-slate-400 shrink-0" />
                                                <span class="text-sm font-semibold text-primary-800 leading-snug">{{ $link['label'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        @error('nav_permissions') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 space-y-5 xl:sticky xl:top-24 self-start">
                <div class="ds-card p-5 space-y-4">
                    <h3 class="text-sm font-bold text-primary-800 flex items-center gap-2"><x-icon name="key-round" size="16" class="text-slate-400" /> الدور والحالة</h3>
                    <div>
                        <label for="role_id" class="ds-label">الدور (اختياري)</label>
                        <select id="role_id" name="role_id" class="ds-select @error('role_id') !border-red-400 @enderror">
                            <option value="">— بدون دور —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string)$currentRoleId === (string)$role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1" @checked(old('active', $user->active)) class="h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                            حساب مفعّل
                        </label>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 space-y-1.5">
                        <div class="flex gap-2"><x-icon name="shield-alert" size="14" class="shrink-0 mt-0.5" /><span>المستخدم هيشوف فقط الصفحات المختارة في السايدبار، وأي رابط تاني هيكون محمي بـ 403.</span></div>
                    </div>
                </div>
                <div class="ds-card p-5">
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="ds-btn ds-btn-primary w-full"><x-icon name="save" size="16" /> حفظ التغييرات</button>
                        <a href="{{ route('users.index') }}" class="ds-btn ds-btn-secondary w-full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function userAccessForm(selected) {
    return {
        showPassword: false,
        selected: selected || [],
        toggleGroup(perms) {
            const allSelected = perms.every(p => this.selected.includes(p));
            if (allSelected) this.selected = this.selected.filter(p => !perms.includes(p));
            else perms.forEach(p => { if (!this.selected.includes(p)) this.selected.push(p); });
        }
    }
}
</script>
@endsection
