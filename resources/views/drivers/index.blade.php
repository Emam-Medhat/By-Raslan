@extends('layouts.app')

@section('title', 'الطيارين')
@section('page-title', 'الطيارين')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <form method="GET" action="{{ route('drivers.index') }}" class="flex gap-2">
            <select name="location_id" class="ds-input" onchange="this.form.submit()">
                <option value="">كل الفروع</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}" @selected($locationId == $loc->id)>{{ $loc->name }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('drivers.create') }}" class="ds-btn ds-btn-primary rounded px-3 py-2 text-sm font-bold">+ إضافة طيار</a>
    </div>

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr><th class="text-right px-4 py-3">الاسم</th><th class="text-right px-4 py-3">الموبايل</th><th class="text-right px-4 py-3">الفرع</th><th class="text-right px-4 py-3">الحساب</th><th class="text-right px-4 py-3">الحالة</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($drivers as $driver)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $driver->name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $driver->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $driver->location?->name }}</td>
                        <td class="px-4 py-3">{{ $driver->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $driver->active ? 'فعال' : 'موقوف' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">لا يوجد طيارين — أضف طيار واربطه بفرعه.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $drivers->links() }}
</div>
@endsection
