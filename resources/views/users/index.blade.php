@extends('layouts.app')

@section('title', 'المستخدمون')
@section('page-title', 'المستخدمون والصلاحيات')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-primary-800">المستخدمون</h1>
            <p class="text-sm text-slate-500 mt-1">إدارة الحسابات، تفعيلها، وصلاحياتها المباشرة.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('roles.index') }}" class="ds-btn ds-btn-secondary"><x-icon name="shield-check" size="16" /> الأدوار</a>
            <a href="{{ route('users.create') }}" class="ds-btn ds-btn-primary"><x-icon name="plus" size="16" /> إضافة مستخدم</a>
        </div>
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الموبايل</th>
                    <th>الصلاحيات</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="font-semibold">
                                {{ $user->name }}
                                @can('users.manage')<a href="{{ route('users.edit', $user) }}" class="ds-link text-xs mr-2">تعديل</a>@endcan
                            </div>
                            @if ($user->is_owner)
                                <span class="ds-badge ds-badge-warning text-xs">مالك</span>
                            @endif
                        </td>
                        <td class="num">{{ $user->phone }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->permissions as $perm)
                                    <span class="ds-badge ds-badge-primary text-xs">{{ $perm->id }}</span>
                                @empty
                                    <span class="muted text-xs">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <span class="ds-badge {{ $user->active ? 'ds-badge-success' : 'ds-badge-danger' }}">
                                {{ $user->active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @can('users.manage')
                                    <a href="{{ route('users.edit', $user) }}" class="ds-btn ds-btn-sm ds-btn-secondary"><x-icon name="pencil" size="14" /> تعديل</a>
                                @endcan
                                <form method="POST" action="{{ route('users.toggle', $user) }}" class="inline">
                                    @csrf
                                    <button class="ds-btn ds-btn-sm {{ $user->active ? 'ds-btn-danger' : 'ds-btn-primary' }}">{{ $user->active ? 'تعطيل' : 'تفعيل' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ds-empty">لا يوجد مستخدمون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection