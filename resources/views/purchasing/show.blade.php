@extends('layouts.app')

@section('title', 'أمر توريد #'.$order->no)
@section('page-title', 'أمر التوريد #'.$order->no)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5 grid gap-2 sm:grid-cols-4">
        <div><div class="text-xs text-slate-500">المورد</div><div class="font-semibold">{{ $order->supplier?->name }}</div></div>
        <div><div class="text-xs text-slate-500">الحالة</div><div class="font-semibold">{{ $order->status }}</div></div>
        <div><div class="text-xs text-slate-500">المتوقع</div><div class="font-semibold">{{ $order->expected ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-500">ملاحظة</div><div class="font-semibold">{{ $order->note ?? '—' }}</div></div>
    </div>

    @if (in_array($order->status, ['open', 'partial']))
        @can('purchase.edit')
            <form method="POST" action="{{ route('purchasing.receive', $order) }}" class="ds-card p-5 space-y-3">
                @csrf
                <div class="flex items-center gap-3">
                    <input name="invoice_no" placeholder="رقم الفاتورة" class="ds-input">
                    <span class="text-sm text-slate-500">أدخل الموزون والمرفوض والسعر لكل بند</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/80 text-slate-500">
                        <tr>
                            <th class="text-right px-2 py-2">الصنف</th>
                            <th class="text-right px-2 py-2">المطلوب</th>
                            <th class="text-right px-2 py-2">المستلم</th>
                            <th class="text-right px-2 py-2">الموزون</th>
                            <th class="text-right px-2 py-2">المرفوض</th>
                            <th class="text-right px-2 py-2">السعر</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->lines as $line)
                            <tr>
                                <td class="px-2 py-2">{{ $line->item?->name }}</td>
                                <td class="px-2 py-2">{{ $line->qty }}</td>
                                <td class="px-2 py-2">{{ $line->received }}</td>
                                <td class="px-2 py-1"><input name="lines[{{ $line->line }}][weighed]" type="number" step="0.001" min="0" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                                <td class="px-2 py-1"><input name="lines[{{ $line->line }}][rejected]" type="number" step="0.001" min="0" value="0" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                                <td class="px-2 py-1"><input name="lines[{{ $line->line }}][price]" type="number" step="0.01" value="{{ $line->price }}" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="flex gap-2">
                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-5 py-2 font-bold">استلام</button>
                </div>
            </form>
        @endcan>
    @endif

    <div class="ds-card p-5">
        @if (in_array($order->status, ['open', 'partial']))
            @can('purchase.edit')
                <form method="POST" action="{{ route('purchasing.cancel', $order) }}">
                    @csrf
                    <button class="text-rose-600 underline text-sm">إلغاء أمر التوريد</button>
                </form>
            @endcan
        @endif
    </div>
</div>
@endsection