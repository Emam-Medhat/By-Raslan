@extends('layouts.app')

@section('title', 'أمر إنتاج #'.$order->no)
@section('page-title', 'أمر الإنتاج #'.$order->no.' · '.($order->item?->name ?? ''))

@section('content')
<div class="space-y-4">
    @foreach ($order->steps as $step)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-bold">خطوة {{ $step->step }} — {{ $step->section?->name }}</div>
                    <div class="text-xs text-slate-500">الحالة: {{ $step->status }}</div>
                </div>
                <div class="text-xs text-slate-500">
                    داخل: {{ $step->in_weight ?? '—' }} · طالع: {{ $step->out_weight ?? '—' }} · هالك: {{ $step->waste ?? '—' }}
                </div>
            </div>

            @can('prod.orders')
                <div class="mt-3 flex gap-4">
                    @if ($step->status === 'waiting')
                        <form method="POST" action="{{ route('production.start', [$order, $step->step]) }}" class="flex gap-2 items-end">
                            @csrf
                            <div><label class="block text-xs">الوزن الداخلي</label><input name="in_weight" type="number" step="0.001" class="w-24 rounded border border-slate-300 px-2 py-1"></div>
                            <button class="bg-emerald-600 text-white rounded px-3 py-1 text-sm">بدء</button>
                        </form>
                    @elseif ($step->status === 'running')
                        <form method="POST" action="{{ route('production.finish', [$order, $step->step]) }}" class="flex gap-2 items-end">
                            @csrf
                            <div><label class="block text-xs">الوزن الطالع</label><input name="out_weight" type="number" step="0.001" class="w-24 rounded border border-slate-300 px-2 py-1"></div>
                            <div><label class="block text-xs">الهالك</label><input name="waste" type="number" step="0.001" value="0" class="w-20 rounded border border-slate-300 px-2 py-1"></div>
                            <button class="bg-amber-600 text-white rounded px-3 py-1 text-sm">إنهاء</button>
                        </form>
                    @endif
                </div>
            @endcan
        </div>
    @endforeach
</div>
@endsection