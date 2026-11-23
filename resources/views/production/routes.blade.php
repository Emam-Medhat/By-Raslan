@extends('layouts.app')

@section('title', 'مشوار الغرف')
@section('page-title', 'مشوار الغرف')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('production.setRoute', ['item' => 0]) }}"
          onsubmit="this.action = this.action.replace('/items/0/route', '/items/' + this.item_id.value + '/route');"
          class="space-y-4">
        @csrf
        <select name="item_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <div>
            <div class="text-sm font-semibold mb-2">الغرف بالترتيب (تُحفظ بترتيب الاختيار)</div>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($sections as $section)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="sections[]" value="{{ $section->id }}" class="rounded"> {{ $section->name }}</label>
                @endforeach
            </div>
        </div>
        <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-5 py-2 font-bold">حفظ المشوار</button>
    </form>
</div>
@endsection