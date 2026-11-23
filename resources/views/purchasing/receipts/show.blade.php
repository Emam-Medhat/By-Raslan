@extends('layouts.app')

@section('title', 'إذن استلام')
@section('page-title', 'إذن استلام GRN-' . $receipt->no)

@section('content')
<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5 grid gap-2 sm:grid-cols-4">
        <div><div class="text-xs text-slate-500">المورد</div><div class="font-semibold">{{ $receipt->supplier?->name }}</div></div>
        <div><div class="text-xs text-slate-500">أمر التوريد</div><div class="font-semibold">{{ $receipt->purchaseOrder?->no ?? $receipt->po_id }}</div></div>
        <div><div class="text-xs text-slate-500">الفاتورة</div><div class="font-semibold">{{ $receipt->invoice_no ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-500">النتيجة</div><div class="font-semibold">{{ $receipt->result }}</div></div>
    </div>
    @if (!empty($receipt->flags))
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
            @foreach ($receipt->flags as $f)<div>• {{ $f }}</div>@endforeach
        </div>
    @endif
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="text-right px-4 py-3">الصنف</th><th class="text-right px-4 py-3">موزون</th><th class="text-right px-4 py-3">مرفوض</th><th class="text-right px-4 py-3">مقبول</th><th class="text-right px-4 py-3">السعر</th><th class="text-right px-4 py-3">اللوت</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($receipt->lines as $l)
                    <tr>
                        <td class="px-4 py-3">{{ $l->item?->name }}</td>
                        <td class="px-4 py-3">{{ $l->weighed }}</td>
                        <td class="px-4 py-3">{{ $l->rejected }}</td>
                        <td class="px-4 py-3">{{ $l->accepted }}</td>
                        <td class="px-4 py-3">{{ $l->price }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $l->lot_code ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
