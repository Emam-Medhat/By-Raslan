@extends('layouts.app')

@section('title', 'مرتجع جديد')
@section('page-title', 'مرتجع جديد')

@section('content')
<div class="max-w-2xl space-y-4">
    {{-- Step 1: pick the invoice (a return is only possible after an invoice exists) --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <form method="GET" action="{{ route('returns.create') }}" class="flex gap-3">
            <select name="sale_id" class="flex-1 ds-input" onchange="this.form.submit()">
                <option value="">— اختار الفاتورة —</option>
                @foreach ($sales as $s)
                    <option value="{{ $s->id }}" @selected($sale && $sale->id === $s->id)>#{{ $s->no }} — {{ number_format($s->total, 2) }} ج</option>
                @endforeach
            </select>
        </form>
        @if (!$sale)
            <p class="mt-3 text-sm text-slate-500">المرتجع لازم يكون بعد فاتورة موجودة. اختار الفاتورة عشان تظهر أصنافها.</p>
        @endif
    </div>

    @if ($sale)
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <form method="POST" action="{{ route('returns.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            <div>
                <div class="ds-label">أصناف الفاتورة #{{ $sale->no }} — علّم على اللي راجع (الكل متعلّم = إرجاع الفاتورة كلها)</div>
                <div class="space-y-2">
                    @foreach ($sale->lines as $line)
                        <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-3 py-2 cursor-pointer">
                            <input type="checkbox" name="line_ids[]" value="{{ $line->id }}" checked class="h-4 w-4">
                            <span class="flex-1 font-semibold">{{ $line->item?->name ?? 'صنف' }}</span>
                            @if ($line->box_sn)<span class="text-xs text-slate-500">علبة #{{ $line->box_sn }}</span>@endif
                            <span class="text-sm text-slate-500">الكمية {{ $line->qty }}</span>
                            <span class="num font-bold">{{ number_format($line->total, 2) }} ج</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">لو عايز ترجّع صنف واحد بس، شيل العلامة من باقي الأصناف.</p>
            </div>

            <div>
                <label class="ds-label">وجهة المرتجع (حسب حالة العلبة)</label>
                <select name="destination" class="w-full ds-input">
                    <option value="stock">مخزون الفرع — ترجع تتباع تاني</option>
                    <option value="waste">هالك — بايظة ومش هتتباع</option>
                    <option value="donated">مخزن تبرعات — تروح لدار أيتام أو جمعية</option>
                </select>
            </div>

            <div>
                <label class="ds-label">الفرع اللي رجع له المرتجع</label>
                <select name="location_id" class="w-full ds-input">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected($location->id === $sale->location_id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="ds-label">طريقة استرداد الفلوس للعميل</label>
                <select name="refund_method" class="w-full ds-input">
                    <option value="cash">كاش (يخصم من عهدة الوردية)</option>
                    <option value="card">فيزا / محفظة</option>
                    <option value="none">بدون استرداد (استبدال مثلاً)</option>
                </select>
            </div>

            <textarea name="reason" rows="2" placeholder="سبب الإرجاع" class="w-full ds-input"></textarea>
            <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">تسجيل المرتجع</button>
        </form>
    </div>
    @endif
</div>
@endsection
