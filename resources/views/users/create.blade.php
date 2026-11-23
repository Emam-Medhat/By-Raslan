@extends('layouts.app')

@section('title', 'إضافة مستخدم')
@section('page-title', 'إضافة مستخدم')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم</label>
                <input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الموبايل</label>
                <input name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الإيميل (اختياري)</label>
                <input name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الرقم السري</label>
                <input type="password" name="password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="active" checked class="rounded"> حساب مفعّل
        </label>

        <div>
            <div class="text-sm font-semibold mb-2">الصلاحيات المباشرة</div>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($permissions as $perm)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="rounded">
                        {{ $perm->label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">حفظ</button>
            <a href="{{ route('users.index') }}" class="text-slate-600 px-4 py-2">إلغاء</a>
        </div>
    </form>
</div>
@endsection