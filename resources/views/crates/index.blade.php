@extends('layouts.app')

@section('title', 'الصناديق')
@section('page-title', 'إدارة الصناديق (الك runAfter)')

@section('content')
<div class="space-y-4">
    @can('settings.edit')
        <form method="POST" action="{{ route('crates.store') }}" class="flex gap-2 max-w-md">
            @csrf
            <input name="id" placeholder="معرّف الصندوق (مثل CR-041)" class="flex-1 rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold">إضافة</button>
        </form>
    @endcan

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الصندوق</th>
                    <th class="text-right px-4 py-3">الحالة</th>
                    <th class="text-right px-4 py-3">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($crates as $crate)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $crate->id }}</td>
                        <td class="px-4 py-3">{{ $crate->active ? 'متاح' : 'معطّل' }}</td>
                        <td class="px-4 py-3">
                            @can('settings.edit')
                                <form method="POST" action="{{ route('crates.toggle', $crate) }}" class="inline">
                                    @csrf <button class="text-xs underline text-slate-600">{{ $crate->active ? 'تعطيل' : 'تفعيل' }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">لا توجد صناديق</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection