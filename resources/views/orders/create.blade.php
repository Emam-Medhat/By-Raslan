@extends('layouts.app')

@section('title', 'إنشاء طلب — خدمة عملاء')
@section('page-title', 'إنشاء طلب (خدمة عملاء)')

@section('content')
<div class="space-y-4">
    @if ($errors->any())
        <div class="ds-card p-4 border-red-200 bg-red-50 text-red-700 text-sm">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
        @csrf
        <div class="ds-card p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ds-label">رقم موبايل العميل *</label>
                <input name="phone" value="{{ old('phone') }}" required class="ds-input" dir="ltr" placeholder="01xxxxxxxxx">
            </div>
            <div>
                <label class="ds-label">اسم العميل *</label>
                <input name="name" value="{{ old('name') }}" required class="ds-input">
            </div>
            <div>
                <label class="ds-label">العنوان *</label>
                <input name="address" value="{{ old('address') }}" required class="ds-input">
            </div>
            <div>
                <label class="ds-label">المنطقة</label>
                <input name="area" value="{{ old('area') }}" class="ds-input" placeholder="مثال: رشدي">
                <p class="text-xs text-slate-400 mt-1">لو المنطقة مربوطة بفرع، الفرع هيتحدد لوحده. لو مش مربوطة، اختار الفرع بنفسك.</p>
            </div>
            <div>
                <label class="ds-label">الفرع (اختياري — يحدد من المنطقة لو فاضي)</label>
                <select name="location_id" class="ds-input">
                    <option value="">— تلقائي من المنطقة —</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('location_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-label">رسوم التوصيل (ج.م)</label>
                <input name="delivery_fee" type="number" step="0.01" min="0" value="{{ old('delivery_fee') }}" class="ds-input" placeholder="افتراضي من إعدادات الفرع">
            </div>
            <div>
                <label class="ds-label">طريقة الدفع المتوقعة</label>
                <select name="pay_method" class="ds-input">
                    <option value="cash" @selected(old('pay_method', 'cash') == 'cash')>كاش</option>
                    <option value="instapay" @selected(old('pay_method') == 'instapay')>إنستا باي</option>
                    <option value="card" @selected(old('pay_method') == 'card')>فيزا / محفظة</option>
                </select>
            </div>
            <div>
                <label class="ds-label">ملاحظات</label>
                <input name="note" value="{{ old('note') }}" class="ds-input">
            </div>
        </div>

        <div class="ds-card p-5">
            <h3 class="font-bold mb-2">الأصناف والكميات</h3>
            <p class="text-xs text-slate-400 mb-3">اكتب الكمية جنب الصنف المطلوب بس. السعر بيتحدد من أسعار الفرع (قناة التطبيق).</p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $i => $item)
                    <div class="flex items-center gap-2 rounded border border-slate-200 px-3 py-2">
                        <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $item->id }}">
                        <span class="flex-1 text-sm font-semibold">{{ $item->name }} <span class="text-xs text-slate-400">({{ $item->unit }})</span></span>
                        <input type="number" name="lines[{{ $i }}][qty]" step="0.001" min="0" value="0" class="w-20 rounded border border-slate-300 px-2 py-1 text-center" dir="ltr">
                    </div>
                @endforeach
            </div>
        </div>

        <button class="ds-btn ds-btn-primary rounded px-4 py-2 font-bold">إنشاء الطلب</button>
        <a href="{{ route('orders.index') }}" class="text-sm text-slate-500">رجوع للطلبات</a>
    </form>
</div>
@endsection
