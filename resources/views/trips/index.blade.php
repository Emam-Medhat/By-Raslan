@extends('layouts.app')

@section('title', 'الرحلات')
@section('page-title', 'تحميل العربيات')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">الرحلات</h1>
    @can('trips.load')
        <a href="{{ route('trips.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">رحلة جديدة</a>
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">رقم</th>
                <th class="text-right px-4 py-3">العربية</th>
                <th class="text-right px-4 py-3">السائق</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">العلب</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($trips as $trip)
                <tr>
                    <td class="px-4 py-3 font-semibold">#{{ $trip->no }}</td>
                    <td class="px-4 py-3">{{ $trip->truck }}</td>
                    <td class="px-4 py-3">{{ $trip->driver ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $trip->status }}</td>
                    <td class="px-4 py-3">{{ $trip->boxes_count }}</td>
                    <td class="px-4 py-3"><a href="{{ route('trips.show', $trip) }}" class="underline text-brand-700">فتح</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">لا توجد رحلات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection