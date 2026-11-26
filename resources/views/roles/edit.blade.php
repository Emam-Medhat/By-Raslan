@extends('layouts.app')

@section('title', 'تعديل دور')
@section('page-title', 'تعديل الدور')
@section('page-subtitle', $role->name)

@section('page-actions')
    <a href="{{ route('roles.index') }}" class="ds-btn ds-btn-secondary"><x-icon name="arrow-right" size="16" /> رجوع للأدوار</a>
@endsection

@section('content')
<div class="max-w-5xl">
    <form method="POST" action="{{ route('roles.update', $role) }}" class="ds-card overflow-hidden">
        @csrf
        @method('PUT')
        <div class="p-5 space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="ds-label">اسم الدور <span class="text-red-500">*</span></label>
                    <input name="name" value="{{ old('name', $role->name) }}" class="ds-input @error('name') !border-red-400 @enderror" required>
                    @error('name') <p class="ds-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label">المعرّف (slug) <span class="text-red-500">*</span></label>
                    <input name="slug" value="{{ old('slug', $role->slug) }}" class="ds-input font-mono @error('slug') !border-red-400 @enderror" dir="ltr" required>
                    @error('slug') <p class="ds-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="ds-label">الوصف</label>
                    <input name="description" value="{{ old('description', $role->description) }}" class="ds-input">
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-bold text-primary-800">الصلاحيات</div>
                    <div class="text-xs text-slate-400">التغييرات تنعكس فورًا على المستخدمين المرتبطين بالدور.</div>
                </div>
                @php $selected = old('permissions', $role->permissions->pluck('id')->all()); @endphp
                @foreach($permissions as $group => $perms)
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-extrabold text-slate-500">{{ $group }}</span>
                            <span class="flex-1 h-px bg-slate-100"></span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($perms as $perm)
                                <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50 transition cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" @checked(in_array($perm->id, $selected)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-accent-600 focus:ring-accent-500/30">
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
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
            <a href="{{ route('roles.index') }}" class="ds-btn ds-btn-secondary">إلغاء</a>
            <button type="submit" class="ds-btn ds-btn-primary"><x-icon name="save" size="16" /> حفظ التغييرات</button>
        </div>
    </form>
</div>
@endsection
