@extends('layouts.app')

@section('title', 'الأدوار والصلاحيات')
@section('page-title', 'الأدوار والصلاحيات')
@section('page-subtitle', 'إدارة الأدوار وتحديد الصلاحيات المرتبطة بكل دور')

@section('content')
<div class="space-y-5" x-data="{ createOpen: {{ $errors->any() && old('_form') === 'role_create' ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-primary-800">الأدوار</h1>
            <p class="text-sm text-slate-500 mt-1">الأدوار تجمع الصلاحيات وتُسند للمستخدمين. تغيير صلاحيات الدور ينعكس على المستخدمين المرتبطين به.</p>
        </div>
        <button type="button" class="ds-btn ds-btn-primary" @click="createOpen = true">
            <x-icon name="plus" size="16" />
            إضافة دور
        </button>
    </div>

    <div class="ds-table-wrap">
        <div class="ds-table-scroll">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>الدور</th>
                        <th>المعرّف</th>
                        <th>المستخدمون</th>
                        <th>الصلاحيات</th>
                        <th style="width:180px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="font-bold text-primary-800">{{ $role->name }}</div>
                                @if($role->description)<div class="text-xs text-slate-500 mt-0.5">{{ $role->description }}</div>@endif
                            </td>
                            <td><span class="font-mono text-xs text-slate-500" dir="ltr">{{ $role->slug }}</span></td>
                            <td><span class="ds-badge ds-badge-neutral num">{{ $role->users_count }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-1 max-w-xl">
                                    @foreach($role->permissions->take(6) as $perm)
                                        <span class="ds-badge ds-badge-neutral text-[10px]">{{ $perm->id }}</span>
                                    @endforeach
                                    @if($role->permissions->count() > 6)
                                        <span class="ds-badge ds-badge-neutral text-[10px]">+{{ $role->permissions->count() - 6 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('roles.edit', $role) }}" class="ds-btn ds-btn-sm ds-btn-secondary">تعديل</a>
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline" onsubmit="return confirm('حذف الدور {{ e($role->name) }}؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ds-btn ds-btn-sm ds-btn-ghost text-red-600 hover:bg-red-50">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-ui.empty-state icon="shield-check" title="لا توجد أدوار" description="أنشئ دورًا لتجميع الصلاحيات وإسنادها للمستخدمين." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-primary-950/45 backdrop-blur-sm" @click="createOpen = false" x-transition.opacity></div>
        <div class="relative w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl flex flex-col" @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 shrink-0">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">إضافة دور جديد</h2>
                    <p class="text-xs text-slate-500 mt-0.5">حدد اسم الدور والصلاحيات المسموح بها.</p>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700"><x-icon name="x" size="18" /></button>
            </div>
            <form method="POST" action="{{ route('roles.store') }}" class="flex-1 overflow-y-auto p-5 space-y-5">
                @csrf
                <input type="hidden" name="_form" value="role_create">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="ds-label">اسم الدور <span class="text-red-500">*</span></label>
                        <input name="name" value="{{ old('_form') === 'role_create' ? old('name') : '' }}" class="ds-input @error('name') !border-red-400 @enderror" required>
                        @error('name') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">المعرّف (slug) <span class="text-red-500">*</span></label>
                        <input name="slug" value="{{ old('_form') === 'role_create' ? old('slug') : '' }}" class="ds-input font-mono @error('slug') !border-red-400 @enderror" dir="ltr" required placeholder="cashier">
                        @error('slug') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="ds-label">الوصف</label>
                        <input name="description" value="{{ old('_form') === 'role_create' ? old('description') : '' }}" class="ds-input">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="text-sm font-bold text-primary-800">الصلاحيات</div>
                    @foreach($permissions as $group => $perms)
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-extrabold text-slate-500">{{ $group }}</span>
                                <span class="flex-1 h-px bg-slate-100"></span>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($perms as $perm)
                                    <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50 transition cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" @checked(in_array($perm->id, old('_form') === 'role_create' ? old('permissions', []) : [])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-primary-800">{{ $perm->label }}</span>
                                            <span class="block text-[11px] text-slate-400 font-mono" dir="ltr">{{ $perm->id }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary">إلغاء</button>
                    <button type="submit" class="ds-btn ds-btn-primary"><x-icon name="save" size="16" /> حفظ الدور</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
