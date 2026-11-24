@extends('layouts.app')

@section('title', 'الصناديق')
@section('page-title', 'إدارة الصناديق')

@section('content')
<div class="space-y-4">
    @can('settings.edit')
        <form method="POST" action="{{ route('crates.store') }}" class="flex gap-2 max-w-md">
            @csrf
            <input name="id" placeholder="معرّف الصندوق (مثل CR-041)" class="flex-1 ds-input">
            <button class="ds-btn ds-btn-primary">إضافة</button>
        </form>
    @endcan

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>الصندوق</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($crates as $crate)
                    <tr>
                        <td class="font-semibold">{{ $crate->id }}</td>
                        <td><span class="ds-badge {{ $crate->active ? 'ds-badge-success' : 'ds-badge-neutral' }}">{{ $crate->active ? 'متاح' : 'معطّل' }}</span></td>
                        <td>
                            @can('settings.edit')
                                <form method="POST" action="{{ route('crates.toggle', $crate) }}" class="inline">
                                    @csrf <button class="ds-btn ds-btn-sm ds-btn-secondary">{{ $crate->active ? 'تعطيل' : 'تفعيل' }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="ds-empty">لا توجد صناديق</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection