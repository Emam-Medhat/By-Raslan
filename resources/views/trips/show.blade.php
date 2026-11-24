@extends('layouts.app')

@section('title', 'رحلة #'.$trip->no)
@section('page-title', 'الرحلة #'.$trip->no.' · '.$trip->truck)

@section('content')
<div class="space-y-4" x-data="{ crate: '' }">
    @if ($trip->status === 'loading')
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="ds-card p-5">
                <h3 class="font-bold mb-3">فتح صندوق لفرع</h3>
                <form method="POST" action="{{ route('trips.crates.open', $trip) }}" class="space-y-2">
                    @csrf
                    <select name="crate_id" x-model="crate" class="w-full ds-input">
                        <option value="">— اختر صندوق —</option>
                        @foreach ($crates as $c)
                            <option value="{{ $c->id }}">{{ $c->id }}</option>
                        @endforeach
                    </select>
                    <select name="to_loc" class="w-full ds-input">
                        @foreach ($destinations as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <button class="w-full ds-btn ds-btn-accent rounded-lg px-4 py-2 font-bold">فتح</button>
                </form>
            </div>

            <div class="ds-card p-5">
                <h3 class="font-bold mb-3">تحميل علبة</h3>
                <form method="POST" action="{{ route('trips.load', $trip) }}" class="space-y-2">
                    @csrf
                    <select name="crate_id" x-model="crate" class="w-full ds-input">
                        <option value="">— الصندوق —</option>
                        @foreach ($crates as $c)
                            <option value="{{ $c->id }}">{{ $c->id }}</option>
                        @endforeach
                    </select>
                    <input name="box_sn" placeholder="رقم العلبة" class="w-full ds-input" dir="ltr">
                    <button class="w-full ds-btn ds-btn-primary rounded-lg px-4 py-2 font-bold">تحميل</button>
                </form>
            </div>

            <div class="ds-card p-5">
                <h3 class="font-bold mb-3">إنزال علبة / خروج</h3>
                <form method="POST" action="{{ route('trips.unload', $trip) }}" class="space-y-2 mb-3">
                    @csrf
                    <input name="box_sn" placeholder="رقم العلبة" class="w-full ds-input" dir="ltr">
                    <button class="w-full ds-btn bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-4 py-2 font-bold">إنزال</button>
                </form>
                <form method="POST" action="{{ route('trips.depart', $trip) }}">
                    @csrf
                    <button class="w-full ds-btn ds-btn-danger bg-red-600 text-white border-red-600 hover:bg-red-700 rounded-lg px-4 py-2 font-bold">خروج العربية</button>
                </form>
            </div>
        </div>
    @else
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg px-4 py-3 text-sm">
            العربية خرجت في {{ $trip->left_at }} — الحالة: {{ $trip->status }}
        </div>
    @endif

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الصندوق</th>
                    <th class="text-right px-4 py-3">العلبة</th>
                    <th class="text-right px-4 py-3">الكمية</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($trip->boxes as $tb)
                    <tr class="{{ $tb->removed ? 'line-through text-slate-400' : '' }}">
                        <td class="px-4 py-3">{{ $tb->crate_id }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $tb->box_sn }}</td>
                        <td class="px-4 py-3">{{ $tb->removed ? 'مُنزل' : 'مُحمّل' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">لا توجد علب</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection