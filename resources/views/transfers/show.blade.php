@extends('layouts.app')

@section('title', 'تحويل #'.$transfer->no)
@section('page-title', 'التحويل #'.$transfer->no)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5 grid gap-2 sm:grid-cols-4">
        <div><div class="text-xs text-slate-500">من</div><div class="font-semibold">{{ $transfer->from_loc }}</div></div>
        <div><div class="text-xs text-slate-500">إلى</div><div class="font-semibold">{{ $transfer->to_loc }}</div></div>
        <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $transfer->status === 'received' ? 'مستلم' : 'في الطريق' }}</div></div>
        <div><div class="text-xs text-slate-500">عدد العلب</div><div class="font-semibold">{{ $transfer->boxes->count() }}</div></div>
    </div>

    @if ($transfer->status === 'transit')
        @can('stock.edit')
            <form method="POST" action="{{ route('transfers.receive', $transfer) }}" class="ds-card p-5 space-y-3">
                @csrf
                <label class="block text-sm font-semibold">امسح العلب المستلمة (اترك الباقي لتسجيله ناقص)</label>
                <textarea name="boxes" rows="3" class="w-full ds-input" dir="ltr"></textarea>
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-5 py-2 font-bold">استلام</button>
            </form>
        @endcan
    @endif

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">العلبة</th>
                    <th class="text-right px-4 py-3">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($transfer->boxes as $box)
                    <tr>
                        <td class="px-4 py-3" dir="ltr">{{ $box->box_sn }}</td>
                        <td class="px-4 py-3">{{ $box->state }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection