@extends('layouts.app')

@section('title', 'هالك وتبرعات')
@section('page-title', 'هالك وتبرعات')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('stock.dispose.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">الموقع</label>
                <select name="location_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">النوع</label>
                <select name="kind" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="waste">هالك</option>
                    <option value="donate">تبرعات</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">أرقام العلب (امسح أو افصل بفاصلة)</label>
            <textarea name="boxes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2" dir="ltr"></textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">السبب</label>
            <input name="reason" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="bg-rose-600 hover:bg-rose-700 text-white rounded-lg px-5 py-2 font-bold">تنفيذ</button>
    </form>
</div>
@endsection