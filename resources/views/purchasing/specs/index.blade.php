@extends('layouts.app')

@section('title', 'كارت الصنف')
@section('page-title', 'مواصفات الأصناف (الجودة)')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    @can('purchase.edit')
        <div class="ds-card p-5">
            <h3 class="font-bold mb-3">إضافة نسخة مواصفة معتمدة</h3>
            <form method="POST" action="{{ route('purchasing.specs.save', ['item' => 0]) }}"
                  onsubmit="this.action = this.action.replace('/specs/0', '/specs/' + this.item_id.value);"
                  class="space-y-3">
                @csrf
                <select name="item_id" class="w-full ds-input">
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <textarea name="checks[]" rows="2" placeholder="بنود الفحص (كل سطر بند)" class="w-full ds-input"></textarea>
                <textarea name="certs[]" rows="2" placeholder="المستندات المطلوبة (كل سطر مستند)" class="w-full ds-input"></textarea>
                <div class="grid grid-cols-2 gap-2">
                    <input name="temp_max" type="number" step="0.1" placeholder="أقصى حرارة" class="ds-input">
                    <input name="min_shelf_days" type="number" placeholder="أقل صلاحية (يوم)" class="ds-input">
                </div>
                <button class="ds-btn ds-btn-primary">حفظ واعتماد</button>
            </form>
        </div>
    @endcan

    <div class="ds-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3">الصنف</th>
                    <th class="text-right px-4 py-3">النسخة</th>
                    <th class="text-right px-4 py-3">الاعتماد</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($versions as $v)
                    <tr>
                        <td class="px-4 py-3">{{ $v->item?->name }}</td>
                        <td class="px-4 py-3">v{{ $v->version }}</td>
                        <td class="px-4 py-3">{{ $v->approved_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">لا توجد مواصفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection