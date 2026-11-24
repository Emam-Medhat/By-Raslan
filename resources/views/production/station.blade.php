@extends('layouts.app')

@section('title', 'شاشات الإنتاج')
@section('page-title', 'شاشات الإنتاج')

@section('content')
@php
    $roomName = $sections->firstWhere('id', $room)?->name;
    $totalJobs = $jobs->count();
    $runningJobs = $jobs->where('status', 'running')->count();
    $waitingJobs = $jobs->where('status', 'waiting')->count();
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
                <a href="{{ route('production.index') }}" class="hover:text-accent-600 transition">الإنتاج</a>
                <x-icon name="chevron-left" size="14" />
                <span class="text-slate-500 font-semibold">شاشات الإنتاج</span>
            </div>
            <h1 class="text-2xl font-extrabold text-primary-800">شاشات الإنتاج{{ $roomName ? ' — '.$roomName : '' }}</h1>
            <p class="text-sm text-slate-500 mt-1">واجهة تابلت لغرف التصنيع: اختر الغرفة، ابدأ الخطوة بالوزن الداخل، وأنهِها بالوزن الطالع والهالك.</p>
        </div>
        @if($room)
        <div class="flex flex-wrap gap-2">
            <div class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-slate-400"></span><span class="text-slate-500">منتظر</span><b class="text-primary-800 num">{{ $waitingJobs }}</b></div>
            <div class="inline-flex items-center gap-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span><span class="text-amber-700">قيد التنفيذ</span><b class="text-amber-800 num">{{ $runningJobs }}</b></div>
            <div class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm"><x-icon name="layers" size="14" class="text-slate-400" /><span class="text-slate-500">الإجمالي</span><b class="text-primary-800 num">{{ $totalJobs }}</b></div>
        </div>
        @endif
    </div>

    {{-- Room selector --}}
    <div class="ds-card p-4 sm:p-5">
        <div class="flex items-center justify-between gap-3 mb-3">
            <div class="text-sm font-bold text-primary-800"><x-icon name="store" size="16" class="ml-1 text-slate-400" /> اختر الغرفة</div>
            @if($room)
                <a href="{{ route('production.station') }}" class="text-xs font-semibold text-slate-400 hover:text-primary-800 transition">مسح الاختيار</a>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @forelse ($sections as $s)
                @php $active = $room === $s->id; @endphp
                <a href="{{ route('production.station', ['room' => $s->id]) }}"
                   class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold transition-all border {{ $active ? 'bg-primary-800 text-white border-primary-800 shadow-lg shadow-primary-800/20' : 'bg-white text-slate-600 border-slate-200 hover:border-accent-300 hover:bg-accent-50/40 hover:text-primary-800' }}">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg {{ $active ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500' }}"><x-icon name="factory" size="14" /></span>
                    {{ $s->name }}
                    @if($active)<x-icon name="circle-check" size="14" class="text-accent-300" />@endif
                </a>
            @empty
                <div class="text-sm text-slate-400">لا توجد غرف إنتاج مفعّلة. أضف مواقع من نوع <code>section</code>.</div>
            @endforelse
        </div>
    </div>

    @if ($room === '')
        <div class="ds-card p-10 sm:p-14 text-center">
            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                <x-icon name="tablet-screen-button" size="28" />
            </div>
            <h2 class="text-lg font-extrabold text-primary-800">اختر غرفة لبدء العمل</h2>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">هذه الشاشة مصممة للتابلت داخل غرفة الإنتاج. بعد اختيار الغرفة، احفظ الصفحة على الشاشة الرئيسية للجهاز.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @forelse ($jobs as $j)
                @php
                    $isRunning = $j->status === 'running';
                    $isWaiting = $j->status === 'waiting';
                @endphp
                <article class="ds-card overflow-hidden {{ $isRunning ? 'ring-2 ring-amber-300/70' : '' }}">
                    <div class="px-5 py-4 border-b {{ $isRunning ? 'bg-amber-50/60 border-amber-100' : 'bg-slate-50/50 border-slate-100' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="ds-badge ds-badge-neutral font-mono">#{{ $j->no }}</span>
                                    @if($isRunning)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset ring-amber-200/60"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> قيد التنفيذ</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-600 px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset ring-slate-200/60"><x-icon name="hourglass-half" size="14" /> بانتظار البدء</span>
                                    @endif
                                    @if($j->last)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 text-accent-700 px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset ring-accent-200/60"><x-icon name="check-circle" size="14" /> خطوة أخيرة</span>
                                    @endif
                                </div>
                                <h3 class="mt-2 text-lg font-extrabold text-primary-800 truncate">{{ $j->item }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">خطوة {{ $j->step }} · الكمية المطلوبة <span class="font-bold text-slate-700 num">{{ rtrim(rtrim(number_format((float)$j->qty,3,'.',''),'0'),'.') }}</span> {{ $j->unit }}</p>
                            </div>
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $isRunning ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-500' }}">
                                <x-icon :name="$isRunning ? 'factory' : 'clock'" size="20" />
                            </div>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">من السابقة</div>
                                <div class="mt-1 font-extrabold text-primary-800 num text-sm">{{ $j->prev_out !== null ? rtrim(rtrim(number_format((float)$j->prev_out,3,'.',''),'0'),'.') : '—' }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">داخل</div>
                                <div class="mt-1 font-extrabold text-primary-800 num text-sm">{{ $j->in_weight !== null ? rtrim(rtrim(number_format((float)$j->in_weight,3,'.',''),'0'),'.') : '—' }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">الحالة</div>
                                <div class="mt-1 font-extrabold text-sm {{ $isRunning ? 'text-amber-600' : 'text-slate-500' }}">{{ $isRunning ? 'يعمل' : 'منتظر' }}</div>
                            </div>
                        </div>

                        @can('prod.orders')
                            @if ($isWaiting)
                                <form method="POST" action="{{ route('production.start', [$j->order, $j->step]) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="ds-label">الوزن الداخل (كجم) <span class="text-red-500">*</span></label>
                                        <input name="in_weight" type="number" step="0.001" min="0.001" required value="{{ $j->prev_out }}" class="ds-input h-12 text-base font-semibold num" placeholder="0.000">
                                        @if($j->prev_out !== null)
                                            <p class="text-[11px] text-slate-400 mt-1">مقترح من خرج الغرفة السابقة: {{ rtrim(rtrim(number_format((float)$j->prev_out,3,'.',''),'0'),'.') }}</p>
                                        @endif
                                    </div>
                                    <button class="ds-btn ds-btn-accent w-full h-12 text-base"><x-icon name="arrow-right" size="16" class="ml-1" /> بدء العمل</button>
                                </form>
                            @elseif ($isRunning)
                                <form method="POST" action="{{ route('production.finish', [$j->order, $j->step]) }}" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="ds-label">الوزن الطالع <span class="text-red-500">*</span></label>
                                            <input name="out_weight" type="number" step="0.001" min="0.001" required class="ds-input h-12 text-base font-semibold num" placeholder="0.000">
                                        </div>
                                        <div>
                                            <label class="ds-label">الهالك</label>
                                            <input name="waste" type="number" step="0.001" min="0" value="0" class="ds-input h-12 text-base font-semibold num">
                                        </div>
                                    </div>
                                    <button class="ds-btn w-full h-12 text-base bg-amber-600 border-amber-600 text-white hover:bg-amber-700 focus:ring-amber-500/30">
                                        <x-icon name="check-circle" size="16" class="ml-1" /> إنهاء{{ $j->last ? ' — إرسال للتغليف' : ' الخطوة' }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </article>
            @empty
                <div class="md:col-span-2 2xl:col-span-3 ds-card p-10 sm:p-14 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                        <x-icon name="mug-hot" size="24" />
                    </div>
                    <h2 class="text-lg font-extrabold text-primary-800">لا يوجد شغل حاليًا</h2>
                    <p class="text-sm text-slate-500 mt-1">هذه الغرفة لا تحتوي على خطوات إنتاج منتظرة أو قيد التنفيذ الآن.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection
