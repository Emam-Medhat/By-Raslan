@extends('layouts.app')

@section('title', 'تعديل مستخدم')
@section('page-title', 'تعديل المستخدم')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الموبايل</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الإيميل</label>
                <input name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">رقم سري جديد (اتركه فارغًا للإبقاء)</label>
                <input type="password" name="password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="active" @checked(old('active', $user->active)) class="rounded"> حساب مفعّل
        </label>
        <div class="flex gap-3">
            <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">حفظ</button>
            <a href="{{ route('users.index') }}" class="text-slate-600 px-4 py-2">إلغاء</a>
        </div>
    </form>
</div>
@endsection