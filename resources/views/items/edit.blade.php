@extends('layouts.app')

@section('title', 'تعديل صنف')
@section('page-title', 'تعديل الصنف')

@section('content')
<div class="max-w-2xl bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="{{ route('items.update', $item) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">اسم الصنف</label>
                <input name="name" value="{{ old('name', $item->name) }}" class="w-full ds-input">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">العائلة</label>
                <select name="family_id" class="w-full ds-input">
                    <option value="">—</option>
                    @foreach ($families as $family)
                        <option value="{{ $family->id }}" @selected(old('family_id', $item->family_id) == $family->id)>{{ $family->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">النوع</label>
                <select name="kind" class="w-full ds-input">
                    @foreach (\App\Modules\Catalog\Models\Item::KINDS as $value => $label)
                        <option value="{{ $value }}" @selected(old('kind', $item->kind) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الوحدة</label>
                <input name="unit" value="{{ old('unit', $item->unit) }}" class="w-full ds-input">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">PLU الميزان</label>
                <input name="scale_plu" value="{{ old('scale_plu', $item->scale_plu) }}" class="w-full ds-input" dir="ltr">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold mb-1">الباركود (افصل بفاصلة)</label>
                <input name="barcodes" value="{{ old('barcodes', $item->barcodes->pluck('code')->implode(', ')) }}" class="w-full ds-input" dir="ltr">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" @checked(old('active', $item->active ? 1 : 0) == 1) class="rounded"> مفعّل
        </label>
        <div class="flex gap-3">
            <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">حفظ</button>
            <a href="{{ route('items.index') }}" class="text-slate-600 px-4 py-2">إلغاء</a>
        </div>
    </form>
</div>
@endsection