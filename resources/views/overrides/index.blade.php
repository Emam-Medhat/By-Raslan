@extends('layouts.app')

@section('title', 'التجاوزات')
@section('page-title', 'التجاوزات')

@section('content')
@php
    $kindMeta = [
        'price' => ['label' => 'سعر', 'icon' => 'tag', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200/60'],
        'qty' => ['label' => 'كمية', 'icon' => 'boxes', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200/60'],
        'discount' => ['label' => 'خصم', 'icon' => 'percent', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200/60'],
        'recv' => ['label' => 'استلام', 'icon' => 'package-open', 'class' => 'bg-teal-50 text-teal-700 ring-teal-200/60'],
    ];
    $statusMeta = [
        'pending' => ['label' => 'بانتظار القرار', 'icon' => 'clock', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200/60'],
        'approved' => ['label' => 'مقبول', 'icon' => 'circle-check', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60'],
        'rejected' => ['label' => 'مرفوض', 'icon' => 'x-circle', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200/60'],
    ];
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-primary-800">طلبات التجاوز</h1>
            <p class="text-sm text-slate-500 mt-0.5">مراجعة واعتماد التجاوزات التي تتطلب قرارًا إداريًا.</p>
        </div>
        <div class="ds-badge ds-badge-neutral">{{ $overrides->total() }} طلب</div>
    </div>

    <div class="ds-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ds-table min-w-[920px]">
                <thead>
                    <tr>
                        <th class="!w-20">#</th>
                        <th>النوع</th>
                        <th>المرجع</th>
                        <th>القيمة</th>
                        <th>السبب</th>
                        <th>الحالة</th>
                        <th class="!text-left">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overrides as $override)
                        @php
                            $k = $kindMeta[$override->kind] ?? ['label' => $override->kind, 'icon' => 'info', 'class' => 'bg-slate-100 text-slate-600 ring-slate-200/60'];
                            $s = $statusMeta[$override->status] ?? ['label' => $override->status, 'icon' => 'circle-dot', 'class' => 'bg-slate-100 text-slate-600 ring-slate-200/60'];
                        @endphp
                        <tr>
                            <td class="font-bold text-slate-500">#{{ $override->id }}</td>
                            <td>
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ring-1 ring-inset {{ $k['class'] }}">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/70"><x-icon :name="$k['icon']" size="13" /></span>
                                    {{ $k['label'] }}
                                </span>
                            </td>
                            <td class="font-medium text-slate-700" dir="ltr">{{ $override->ref ?? '—' }}</td>
                            <td class="num font-bold text-primary-800">{{ $override->value }}</td>
                            <td class="max-w-[320px]"><div class="truncate text-slate-600" title="{{ $override->reason }}">{{ $override->reason }}</div></td>
                            <td>
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ring-1 ring-inset {{ $s['class'] }}">
                                    <x-icon :name="$s['icon']" size="13" />
                                    {{ $s['label'] }}
                                </span>
                            </td>
                            <td>
                                @can('override.recv')
                                    @if ($override->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('overrides.decide', $override) }}">
                                                @csrf
                                                <input type="hidden" name="approve" value="1">
                                                <button class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200/70 hover:bg-emerald-100 transition" title="موافقة">
                                                    <x-icon name="check" size="16" />
                                                    <span>موافقة</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('overrides.decide', $override) }}">
                                                @csrf
                                                <input type="hidden" name="approve" value="0">
                                                <button class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-200/70 hover:bg-rose-100 transition" title="رفض">
                                                    <x-icon name="x" size="16" />
                                                    <span>رفض</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">تم اتخاذ القرار</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><x-icon name="shield-check" size="20" /></div>
                                    <div class="font-semibold text-slate-500">لا توجد طلبات تجاوز</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $overrides->links() }}</div>
</div>
@endsection