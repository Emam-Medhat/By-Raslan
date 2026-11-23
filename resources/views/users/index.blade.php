@extends('layouts.app')

@section('title', 'المستخدمون')
@section('page-title', 'المستخدمون والصلاحيات')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">المستخدمون</h1>
    <a href="{{ route('users.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">إضافة مستخدم</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3 font-semibold">الاسم</th>
                <th class="text-right px-4 py-3 font-semibold">الموبايل</th>
                <th class="text-right px-4 py-3 font-semibold">الصلاحيات</th>
                <th class="text-right px-4 py-3 font-semibold">الحالة</th>
                <th class="text-right px-4 py-3 font-semibold">إجراءات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-semibold">
                            {{ $user->name }}
                            @can('users.manage')<a href="{{ route('users.edit', $user) }}" class="text-xs text-brand-700 underline mr-2">تعديل</a>@endcan
                        </div>
                        @if ($user->is_owner)
                            <span class="text-xs text-amber-600">مالك</span>
                        @endif
                    </td>
                    <td class="px-4 py-3" dir="ltr">{{ $user->phone }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->permissions as $perm)
                                <span class="text-xs bg-slate-100 rounded px-2 py-0.5">{{ $perm->id }}</span>
                            @empty
                                <span class="text-xs text-slate-400">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs rounded px-2 py-0.5 {{ $user->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $user->active ? 'مفعّل' : 'معطّل' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('users.toggle', $user) }}" class="inline">
                            @csrf
                            <button class="text-xs underline text-slate-600">
                                {{ $user->active ? 'تعطيل' : 'تفعيل' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا يوجد مستخدمون</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection