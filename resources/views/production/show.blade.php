@extends('layouts.app')

@section('title', 'أمر إنتاج #'.$order->no)
@section('page-title', 'أمر إنتاج #'.$order->no)

@section('content')
@php
    $statusMeta = [
        'planned' => ['label' => 'مخطط', 'badge' => 'ds-badge-neutral', 'icon' => 'calendar'],
        'running' => ['label' => 'قيد التنفيذ', 'badge' => 'ds-badge-warning', 'icon' => 'factory'],
        'done' => ['label' => 'مكتمل', 'badge' => 'ds-badge-success', 'icon' => 'circle-check'],
        'cancelled' => ['label' => 'ملغي', 'badge' => 'ds-badge-danger', 'icon' => 'ban'],
    ];
    $stepMeta = [
        'waiting' => ['label' => 'بانتظار البدء', 'badge' => 'bg-slate-100 text-slate-600 ring-slate-200/60', 'dot' => 'bg-slate-400', 'icon' => 'fa-hourglass-half'],
        'running' => ['label' => 'قيد التنفيذ', 'badge' => 'bg-amber-50 text-amber-700 ring-amber-200/60', 'dot' => 'bg-amber-500', 'icon' => 'fa-spinner'],
        'done' => ['label' => 'مكتمل', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60', 'dot' => 'bg-emerald-500', 'icon' => 'fa-check'],
    ];
    $os = $statusMeta[$order->status] ?? $statusMeta['planned'];
    $totalSteps = $order->steps->count();
    $doneSteps = $order->steps->where('status', 'done')->count();
    $progress = $totalSteps > 0 ? round(($doneSteps / $totalSteps) * 100) : 0;
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('production.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-primary-800 transition">
                    <x-icon name="arrow-right" size="16" />
                </a>
                <h1 class="text-xl sm:text-2xl font-extrabold text-primary-800 truncate">أمر إنتاج #{{ $order->no }}</h1>
                <span class="ds-badge {{ $os['badge'] }} gap-1"><x-icon :name="$os['icon']" size="12" /> {{ $os['label'] }}</span>
            </div>
            <p class="text-sm text-slate-500 mt-1 mr-11">{{ $order->item?->name ?? '—' }} @if($order->note) · {{ $order->note }} @endif</p>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ds-kpi">
            <div class="ds-kpi-label">الصنف</div>
            <div class="mt-1 font-extrabold text-primary-800 text-base truncate">{{ $order->item?->name ?? '—' }}</div>
        </div>
        <div class="ds-kpi">
            <div class="ds-kpi-label">الكمية المطلوبة</div>
            <div class="mt-1 font-extrabold text-primary-800 text-xl num">{{ rtrim(rtrim(number_format((float)$order->qty, 3, '.', ''), '0'), '.') }} <span class="text-xs font-semibold text-slate-400">{{ $order->item?->unit }}</span></div>
        </div>
        <div class="ds-kpi">
            <div class="ds-kpi-label">الموعد</div>
            <div class="mt-1 font-extrabold text-primary-800 text-base">{{ optional($order->due)->format('Y-m-d') ?? $order->due ?? '—' }}</div>
        </div>
        <div class="ds-kpi">
            <div class="flex items-center justify-between">
                <div class="ds-kpi-label">التقدم</div>
                <span class="text-xs font-bold text-slate-500">{{ $doneSteps }}/{{ $totalSteps }}</span>
            </div>
            <div class="mt-2 h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-l from-accent-500 to-accent-400 transition-all" style="width: {{ $progress }}%"></div>
            </div>
            <div class="mt-1 text-[11px] font-semibold text-slate-400">{{ $progress }}% مكتمل</div>
        </div>
    </div>

    {{-- Steps timeline --}}
    <div class="ds-card overflow-hidden">
        <div class="ds-card-header">
            <h2 class="ds-card-title"><x-icon name="git-branch" size="16" class="ml-1 text-slate-400" /> خطوات الإنتاج</h2>
            <span class="text-xs text-slate-400">{{ $totalSteps }} غرفة</span>
        </div>
        <div class="p-4 sm:p-5 space-y-4">
            @forelse ($order->steps->sortBy('step') as $step)
                @php
                    $sm = $stepMeta[$step->status] ?? $stepMeta['waiting'];
                    $isLast = $loop->last;
                @endphp
                <div class="relative flex gap-4">
                    {{-- timeline rail --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $step->status === 'done' ? 'bg-emerald-500 text-white' : ($step->status === 'running' ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-500') }} shadow-sm">
                            @if($step->status === 'done')
                                <x-icon name="check" size="16" />
                            @elseif($step->status === 'running')
                                <x-icon name="spin" size="16" />
                            @else
                                <span class="text-sm font-extrabold">{{ $step->step }}</span>
                            @endif
                        </div>
                        @unless($isLast)
                            <div class="w-px flex-1 my-1 {{ $step->status === 'done' ? 'bg-emerald-300' : 'bg-slate-200' }}"></div>
                        @endunless
                    </div>

                    {{-- card --}}
                    <div class="flex-1 pb-2 {{ $isLast ? '' : 'pb-4' }}">
                        <div class="rounded-2xl border {{ $step->status === 'running' ? 'border-amber-200 bg-amber-50/20' : ($step->status === 'done' ? 'border-emerald-200/70 bg-emerald-50/10' : 'border-slate-200 bg-white') }} p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-extrabold text-primary-800">{{ $step->section?->name ?? $step->section_id }}</span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $sm['badge'] }}">
                                            <x-icon :name="$sm['icon']" size="12" /> {{ $sm['label'] }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1">خطوة {{ $step->step }} من {{ $totalSteps }}</div>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1.5 text-slate-600"><span class="text-slate-400">داخل</span> <b class="num text-primary-800">{{ $step->in_weight ?? '—' }}</b></span>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1.5 text-slate-600"><span class="text-slate-400">طالع</span> <b class="num text-primary-800">{{ $step->out_weight ?? '—' }}</b></span>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1.5 text-slate-600"><span class="text-slate-400">هالك</span> <b class="num {{ (float)$step->waste > 0 ? 'text-rose-600' : 'text-primary-800' }}">{{ $step->waste ?? '—' }}</b></span>
                                </div>
                            </div>

                            @if($step->started_at || $step->ended_at)
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-400">
                                    @if($step->started_at)<span><x-icon name="clock" size="16" class="ml-1" />بدء: <span class="num">{{ $step->started_at->format('Y-m-d H:i') }}</span></span>@endif
                                    @if($step->ended_at)<span><x-icon name="check-circle" size="16" class="ml-1" />انتهاء: <span class="num">{{ $step->ended_at->format('Y-m-d H:i') }}</span></span>@endif
                                </div>
                            @endif

                            @can('prod.orders')
                                @if ($step->status === 'waiting')
                                    <form method="POST" action="{{ route('production.start', [$order, $step->step]) }}" class="mt-4 flex flex-wrap gap-3 items-end border-t border-slate-100 pt-4">
                                        @csrf
                                        <div>
                                            <label class="ds-label">الوزن الداخلي <span class="text-red-500">*</span></label>
                                            <input name="in_weight" type="number" step="0.001" min="0.001" required class="ds-input w-40" placeholder="0.000">
                                        </div>
                                        <button class="ds-btn ds-btn-accent px-4"><x-icon name="arrow-right" size="14" class="ml-1" /> بدء الخطوة</button>
                                    </form>
                                @elseif ($step->status === 'running')
                                    <form method="POST" action="{{ route('production.finish', [$order, $step->step]) }}" class="mt-4 flex flex-wrap gap-3 items-end border-t border-amber-100 pt-4">
                                        @csrf
                                        <div>
                                            <label class="ds-label">الوزن الطالع <span class="text-red-500">*</span></label>
                                            <input name="out_weight" type="number" step="0.001" min="0.001" required class="ds-input w-36" placeholder="0.000">
                                        </div>
                                        <div>
                                            <label class="ds-label">الهالك</label>
                                            <input name="waste" type="number" step="0.001" min="0" value="0" class="ds-input w-32">
                                        </div>
                                        <button class="ds-btn ds-btn-primary px-4 bg-amber-600 border-amber-600 hover:bg-amber-700"><x-icon name="check-circle" size="14" class="ml-1" /> إنهاء الخطوة</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-400">
                    <x-icon name="layers" size="28" class="mb-3 block text-slate-300" />
                    لا توجد خطوات لهذا الأمر. تأكد من وجود مسار غرف للصنف.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
