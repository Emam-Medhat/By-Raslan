@extends('layouts.app')

@section('title', 'الحجوزات')
@section('page-title', 'الحجوزات')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">الحجوزات</h1>
        <a href="{{ route('reservations.create') }}" class="ds-btn ds-btn-primary rounded px-4 py-2 font-bold">حجز جديد</a>
    </div>

    <form method="GET" class="ds-card p-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="ds-label">التاريخ</label>
            <input type="date" name="date" value="{{ request('date') }}" class="ds-input">
        </div>
        <div>
            <label class="ds-label">الحالة</label>
            <select name="status" class="ds-input">
                <option value="">الكل</option>
                <option value="pending" @selected(request('status') === 'pending')>معلق</option>
                <option value="converted" @selected(request('status') === 'converted')>اتحول لأوردر</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>ملغي</option>
            </select>
        </div>
        <div>
            <label class="ds-label">الفرع</label>
            <select name="location_id" class="ds-input">
                <option value="">الكل</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('location_id') === $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="ds-btn rounded px-4 py-2 font-bold">فلتر</button>
        <a href="{{ route('reports.factory-reservations') }}" class="ds-link text-sm">تقرير المصنع (حجوزات الغد) ←</a>
    </form>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>الفرع</th>
                    <th>الحالة</th>
                    <th>الأوردر</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $reservation)
                    <tr>
                        <td class="font-semibold">{{ $reservation->reservation_date->toDateString() }}</td>
                        <td>
                            {{ $reservation->customer?->name }}
                            <div class="text-xs muted">{{ $reservation->customer?->phone }}</div>
                        </td>
                        <td>{{ $reservation->location?->name ?? $reservation->location_id }}</td>
                        <td>
                            <span class="ds-badge ds-badge-neutral">
                                @if ($reservation->status === 'pending') معلق
                                @elseif ($reservation->status === 'converted') اتحول لأوردر
                                @else ملغي
                                @endif
                            </span>
                        </td>
                        <td>
                            @if ($reservation->convertedOrder)
                                <a href="{{ route('orders.show', $reservation->convertedOrder) }}" class="ds-link">#{{ $reservation->convertedOrder->no }}</a>
                            @else — @endif
                        </td>
                        <td><a href="{{ route('reservations.show', $reservation) }}" class="ds-link">فتح</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد حجوزات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reservations->links() }}</div>
</div>
@endsection
