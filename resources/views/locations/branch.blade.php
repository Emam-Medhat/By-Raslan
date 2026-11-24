@extends('layouts.app')

@section('title', $location->name)
@section('page-title', $location->name)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5">
        <div class="text-sm text-slate-500">{{ \App\Modules\Organization\Models\Location::TYPES[$location->type] ?? $location->type }}@if($location->city) · {{ $location->city }}@endif</div>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('stock.index', ['loc' => $location->id]) }}" class="ds-input text-sm">المخزون</a>
            @if(in_array($location->type, ['dark','shop']))
                @can('pos.use')<a href="{{ route('pos.index') }}" class="ds-input text-sm">الكاشير</a>@endcan
                @can('orders.view')<a href="{{ route('orders.index') }}" class="ds-input text-sm">الطلبات</a>@endcan
            @endif
            @can('trips.receive')<a href="{{ route('trips.index') }}" class="ds-input text-sm">الرحلات</a>@endcan
        </div>
    </div>
    @if($subs->isNotEmpty())
        <div class="ds-card p-5">
            <h2 class="font-bold mb-3">تابع لـ {{ $location->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($subs as $s)
                    <a href="{{ route('branches.show', $s) }}" class="ds-input text-sm">{{ $s->name }}</a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
