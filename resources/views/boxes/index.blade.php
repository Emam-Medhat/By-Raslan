@extends('layouts.app')

@section('title', 'تتبّع العلبة')
@section('page-title', 'تتبّع العلبة')

@section('content')
@php
    $statusLabels = ['stock'=>'بالمخزون','loaded'=>'محمّلة','sold'=>'مباعة','waste'=>'هالك','donated'=>'متبرع بها','missing'=>'ناقصة'];
    $statusBadge = fn($s) => match($s) {
        'stock' => 'ds-badge-success',
        'loaded', 'transit' => 'ds-badge-warning',
        'sold' => 'ds-badge-primary',
        'waste', 'missing', 'lost' => 'ds-badge-danger',
        'donated' => 'ds-badge-accent',
        default => 'ds-badge-neutral',
    };
@endphp
<div class="space-y-4">
    <div class="ds-card p-5">
        <form method="GET" action="{{ route('boxes.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="ds-label">رقم العلبة (SN)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="barcode" size="16" /></span>
                    <input name="sn" value="{{ $sn }}" placeholder="أدخل رقم العلبة..." class="ds-input pr-10" dir="ltr" autofocus>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="ds-btn ds-btn-primary px-5">بحث</button>
                @if($sn)
                    <a href="{{ route('boxes.index') }}" class="ds-btn ds-btn-secondary px-4">مسح</a>
                @endif
            </div>
        </form>
    </div>

    @if ($sn && ! $box)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-center gap-2">
            <x-icon name="alert-triangle" size="16" />
            لا توجد علبة بالرقم <span class="font-bold" dir="ltr">{{ $sn }}</span>
        </div>
    @endif

    @if ($box)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="ds-kpi">
                <div class="ds-kpi-label">الصنف</div>
                <div class="mt-1 font-bold text-primary-800">{{ $box->item?->name ?? '—' }}</div>
            </div>
            <div class="ds-kpi">
                <div class="ds-kpi-label">الوزن</div>
                <div class="mt-1 font-bold text-primary-800 num">{{ rtrim(rtrim(number_format((float)$box->weight, 3, '.', ''), '0'), '.') }}</div>
            </div>
            <div class="ds-kpi">
                <div class="ds-kpi-label">الموقع</div>
                <div class="mt-1 font-bold text-primary-800">{{ $box->location?->name ?? $box->location_id ?? '—' }}</div>
            </div>
            <div class="ds-kpi">
                <div class="ds-kpi-label">الحالة</div>
                <div class="mt-2"><span class="ds-badge {{ $statusBadge($box->status) }}">{{ $statusLabels[$box->status] ?? $box->status }}</span></div>
            </div>
        </div>

        <div class="ds-card p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div><span class="text-slate-400">رقم العلبة:</span> <span class="font-semibold font-mono" dir="ltr">#{{ $box->sn }}</span></div>
            <div><span class="text-slate-400">PLU الميزان:</span> <span class="font-semibold" dir="ltr">{{ $box->scale_code ?? '—' }}</span></div>
            <div><span class="text-slate-400">تاريخ التعبئة:</span> <span class="font-semibold">{{ optional($box->packed_at)->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div><span class="text-slate-400">تاريخ الانتهاء:</span> <span class="font-semibold">{{ optional($box->expires_at)->format('Y-m-d') ?? '—' }}</span></div>
        </div>

        @if ($box->status === 'missing' && auth()->user()?->hasPermission('stock.adjust'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="font-bold text-amber-800 mb-3"><x-icon name="circle-alert" size="16" class="ml-1" /> العلبة متسجلة ناقصة — حسم الحالة</div>
                <form method="POST" action="{{ route('boxes.resolve-missing') }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <input type="hidden" name="sn" value="{{ $box->sn }}">
                    <div class="flex-1 min-w-48">
                        <label class="ds-label">ملاحظة</label>
                        <input name="note" class="ds-input" placeholder="ملاحظة اختيارية">
                    </div>
                    <button name="found" value="1" class="ds-btn ds-btn-accent px-4"><x-icon name="check" size="16" class="ml-1" /> اتلقت</button>
                    <button name="found" value="0" class="ds-btn ds-btn-danger px-4"><x-icon name="x" size="16" class="ml-1" /> ضاعت</button>
                </form>
            </div>
        @endif

        <div class="ds-card overflow-hidden">
            <div class="ds-card-header">
                <h2 class="ds-card-title"><x-icon name="history" size="16" class="ml-1 text-slate-400" /> سجل الحركة</h2>
                <span class="ds-badge ds-badge-neutral">{{ $history->count() }} حركة</span>
            </div>
            <div class="ds-table-wrap border-0 rounded-none shadow-none">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>الموقع</th>
                            <th>المرجع</th>
                            <th>السبب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $move)
                            <tr>
                                <td class="text-xs num" dir="ltr">{{ optional($move->at)->format('Y-m-d H:i') ?? $move->at }}</td>
                                <td><span class="ds-badge ds-badge-neutral">{{ $move->kind }}</span></td>
                                <td class="num font-semibold {{ (float)$move->qty < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ rtrim(rtrim(number_format((float)$move->qty, 3, '.', ''), '0'), '.') }}</td>
                                <td>{{ $move->location?->name ?? $move->location_id }}</td>
                                <td class="text-xs font-mono" dir="ltr">{{ $move->ref ?? '—' }}</td>
                                <td class="text-xs text-slate-500">{{ $move->reason ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ds-empty">لا توجد حركات مسجلة لهذه العلبة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection