@extends('layouts.app')

@section('title', 'الإعدادات')
@section('page-title', 'الإعدادات')

@section('content')
<form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4 max-w-3xl">
    @csrf
    <h2 class="font-bold">الطلبات أونلاين</h2>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="open" value="1" @checked(($all['online.open'] ?? '1') == '1' || ($all['online.open'] ?? true) === true)> استقبال الطلبات مفتوح</label>
    <div class="grid sm:grid-cols-2 gap-3">
        <label class="text-sm">أقل طلب (ج)<input name="min" value="{{ $all['online.min_order'] ?? 300 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr"></label>
        <label class="text-sm">الطلب يبقى متأخر بعد (دقيقة)<input name="late" value="{{ $all['online.late_minutes'] ?? 45 }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr"></label>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach ($locs as $l)
            <label class="text-sm">توصيل {{ $l->name }}<input name="fee:{{ $l->id }}" value="{{ $fees[$l->id] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr"></label>
        @endforeach
    </div>
    <label class="block text-sm">ملاحظة الوزن للعميل<input name="note" value="{{ $all['online.weight_note'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>

    <h2 class="font-bold pt-2">تطبيق العميل</h2>
    <label class="flex items-center gap-2 text-sm text-rose-700"><input type="checkbox" name="test" value="1" @checked(($all['app.test_mode'] ?? false) == true || ($all['app.test_mode'] ?? '0') == '1')> وضع التجربة: الدخول بأي رقم من غير كود SMS — <b>لازم يتقفل قبل ما التطبيق ينزل للناس</b></label>

    @can('settings.edit')
        <button class="bg-brand-600 text-white rounded-lg px-5 py-2 font-bold">حفظ</button>
    @endcan
</form>
@endsection
