@extends('layouts.app')

@section('title', 'تحويل جديد')
@section('page-title', 'تحويل جديد')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">من موقع</label>
                <select name="from_loc" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">إلى موقع</label>
                <select name="to_loc" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">أرقام العلب (امسح أو افصل بفاصلة)</label>
            <textarea name="boxes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr"></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <input name="reason" placeholder="السبب" class="rounded-lg border border-slate-300 px-3 py-2">
            <input name="note" placeholder="ملاحظة" class="rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">إرسال</button>
    </form>
</div>
@endsection