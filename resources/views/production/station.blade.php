@extends('layouts.app')

@section('title', 'تابلت الغرف')
@section('page-title', 'تابلت الغرف')

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach ($sections as $s)
            <a href="{{ route('production.station', ['room' => $s->id]) }}" class="rounded-lg px-4 py-2 font-bold {{ $room === $s->id ? 'bg-brand-600 text-white' : 'bg-white border border-slate-200' }}">{{ $s->name }}</a>
        @endforeach
    </div>

    @if ($room === '')
        <div class="bg-white rounded-xl border border-slate-200 p-6 text-slate-500">اختار الغرفة اللي التابلت ده فيها.</div>
    @else
        <div class="space-y-3">
            @forelse ($jobs as $j)
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex justify-between gap-3 mb-3">
                        <div><div class="font-bold">{{ $j->item }}</div><div class="text-sm text-slate-500">أمر #{{ $j->no }} · خطوة {{ $j->step }} · {{ $j->qty }} {{ $j->unit }}</div></div>
                        <span class="text-xs px-2 py-1 rounded bg-slate-100">{{ $j->status }}</span>
                    </div>
                    @if ($j->status === 'waiting')
                        <form method="POST" action="{{ route('production.start', [$j->order, $j->step]) }}" class="flex flex-wrap gap-2 items-end">@csrf
                            <label class="text-sm">الوزن الداخل<input name="in_weight" type="number" step="0.001" required value="{{ $j->prev_out }}" class="mt-1 rounded-lg border border-slate-300 px-3 py-2"></label>
                            <button class="bg-emerald-600 text-white rounded-lg px-4 py-2 font-bold">ابدأ</button>
                        </form>
                    @elseif ($j->status === 'running')
                        <form method="POST" action="{{ route('production.finish', [$j->order, $j->step]) }}" class="flex flex-wrap gap-2 items-end">@csrf
                            <label class="text-sm">الوزن الخارج<input name="out_weight" type="number" step="0.001" required class="mt-1 rounded-lg border border-slate-300 px-3 py-2"></label>
                            <label class="text-sm">الهالك<input name="waste" type="number" step="0.001" value="0" class="mt-1 rounded-lg border border-slate-300 px-3 py-2"></label>
                            <button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold">إنهاء الغرفة</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 p-6 text-slate-500">لا يوجد شغل في هذه الغرفة.</div>
            @endforelse
        </div>
    @endif
</div>
@endsection
