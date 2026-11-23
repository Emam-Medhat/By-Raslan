@extends('layouts.app')

@section('title', 'الأسعار')
@section('page-title', 'أسعار الفروع')

@section('content')
<div class="space-y-4">
    <form method="POST" action="{{ route('prices.save') }}" class="bg-white rounded-xl border border-slate-200 p-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @csrf
        <select name="item_id" class="rounded-lg border border-slate-300 px-3 py-2 lg:col-span-2">
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <select name="location_id" class="rounded-lg border border-slate-300 px-3 py-2">
            @foreach ($locations as $location)
                <option value="{{ $location->id }}">{{ $location->name }}</option>
            @endforeach
        </select>
        <select name="channel" class="rounded-lg border border-slate-300 px-3 py-2">
            <option value="direct">فرع مباشر</option>
            <option value="app">تطبيق</option>
        </select>
        <div class="flex gap-2">
            <input name="price" type="number" step="0.01" min="0" placeholder="السعر" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <button class="bg-brand-600 text-white rounded-lg px-4 py-2 font-bold whitespace-nowrap">حفظ</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الصنف</th>
                    <th class="text-right px-4 py-3">الموقع</th>
                    <th class="text-right px-4 py-3">القناة</th>
                    <th class="text-right px-4 py-3">السعر</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($prices as $price)
                    <tr>
                        <td class="px-4 py-3">{{ $price->item?->name }}</td>
                        <td class="px-4 py-3">{{ $price->location_id }}</td>
                        <td class="px-4 py-3">{{ $price->channel === 'app' ? 'تطبيق' : 'مباشر' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $price->price }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">لا توجد أسعار</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection