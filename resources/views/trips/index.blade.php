@extends('layouts.app')

@section('title', 'الرحلات')
@section('page-title', 'تحميل العربيات')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">الرحلات</h1>
        @can('trips.load')
            <a href="{{ route('trips.create') }}" class="ds-btn ds-btn-primary">رحلة جديدة</a>
        @endcan
    </div>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>العربية</th>
                    <th>السائق</th>
                    <th>الحالة</th>
                    <th>العلب</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td class="font-semibold">#{{ $trip->no }}</td>
                        <td>{{ $trip->truck }}</td>
                        <td>{{ $trip->driver ?? '—' }}</td>
                        <td><span class="ds-badge ds-badge-neutral">{{ $trip->status }}</span></td>
                        <td class="num">{{ $trip->boxes_count }}</td>
                        <td><a href="{{ route('trips.show', $trip) }}" class="ds-link">فتح</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد رحلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection