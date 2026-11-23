@extends('layouts.app')

@section('title', 'تعديل موقع')
@section('page-title', 'تعديل الموقع')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">الاسم</label>
                <input name="name" value="{{ old('name', $location->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">النوع</label>
                <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (\App\Modules\Organization\Models\Location::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $location->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">الموقع الأب</label>
                <select name="parent_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">—</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(old('parent_id', $location->parent_id) === $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">المدينة</label>
                <input name="city" value="{{ old('city', $location->city) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <div class="flex gap-3">
            <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">حفظ</button>
            <a href="{{ route('locations.index') }}" class="text-slate-600 px-4 py-2">إلغاء</a>
        </div>
    </form>
</div>
@endsection