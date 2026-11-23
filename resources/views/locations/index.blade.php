@extends('layouts.app')

@section('title', 'المواقع')
@section('page-title', 'المواقع والفروع')

@section('content')
<div x-data="{ show: false }" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">المواقع</h1>
        @can('settings.edit')
            <button @click="show = !show" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">إضافة موقع</button>
        @endcan
    </div>

    <div x-show="show" x-cloak class="bg-white rounded-xl border border-slate-200 p-5">
        <form method="POST" action="{{ route('locations.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <input name="id" placeholder="المعرّف (latin)" class="rounded-lg border border-slate-300 px-3 py-2">
            <input name="name" placeholder="الاسم" class="rounded-lg border border-slate-300 px-3 py-2">
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach (\App\Modules\Organization\Models\Location::TYPES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input name="city" placeholder="المدينة" class="rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-bold">حفظ</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الاسم</th>
                    <th class="text-right px-4 py-3">النوع</th>
                    <th class="text-right px-4 py-3">الأب</th>
                    <th class="text-right px-4 py-3">المدينة</th>
                    <th class="text-right px-4 py-3">الحالة</th>
                    <th class="text-right px-4 py-3">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($locations as $location)
                    <tr>
                        <td class="px-4 py-3 font-semibold"><a href="{{ route('branches.show', $location) }}" class="text-brand-700 underline">{{ $location->name }}</a> @can('settings.edit')<a href="{{ route('locations.edit', $location) }}" class="text-xs text-slate-500 underline mr-1">تعديل</a>@endcan</td>
                        <td class="px-4 py-3">{{ \App\Modules\Organization\Models\Location::TYPES[$location->type] ?? $location->type }}</td>
                        <td class="px-4 py-3">{{ $location->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $location->city ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs rounded px-2 py-0.5 {{ $location->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $location->active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @can('settings.edit')
                                <form method="POST" action="{{ route('locations.toggle', $location) }}" class="inline">
                                    @csrf
                                    <button class="text-xs underline text-slate-600">{{ $location->active ? 'تعطيل' : 'تفعيل' }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد مواقع</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection