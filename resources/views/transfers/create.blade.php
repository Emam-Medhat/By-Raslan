@extends('layouts.app')

@section('title', 'تحويل جديد')
@section('page-title', 'تحويل جديد')

@section('content')
<div class="max-w-2xl bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">من موقع</label>
                <select name="from_loc" class="w-full ds-input">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">إلى موقع</label>
                <select name="to_loc" class="w-full ds-input">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">أرقام العلب (امسح أو افصل بفاصلة)</label>
            <textarea name="boxes" rows="3" class="w-full ds-input" dir="ltr"></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <input name="reason" placeholder="السبب" class="ds-input">
            <input name="note" placeholder="ملاحظة" class="ds-input">
        </div>
        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">إرسال</button>
    </form>
</div>
@endsection