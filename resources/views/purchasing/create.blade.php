@extends('layouts.app')

@section('title', 'أمر توريد جديد')
@section('page-title', 'أمر توريد جديد')

@section('content')
<div x-data="{ rows: [{ item_id: '', qty: '', price: '' }] }" class="ds-card p-6 space-y-4">
    <form method="POST" action="{{ route('purchasing.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <select name="supplier_id" class="ds-input">
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
            <input name="expected" type="date" class="ds-input">
            <input name="note" placeholder="ملاحظة" class="ds-input">
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr><th class="text-right px-2 py-2">الصنف</th><th class="text-right px-2 py-2">الكمية</th><th class="text-right px-2 py-2">السعر</th><th></th></tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in rows" :key="i">
                    <tr>
                        <td class="px-2 py-1">
                            <select :name="`lines[${i}][item_id]`" x-model="row.item_id" class="w-full rounded border border-slate-300 px-2 py-1">
                                <option value="">—</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-1"><input type="number" step="0.001" :name="`lines[${i}][qty]`" x-model="row.qty" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                        <td class="px-2 py-1"><input type="number" step="0.01" :name="`lines[${i}][price]`" x-model="row.price" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                        <td class="px-2 py-1"><button type="button" @click="rows.splice(i,1)" class="text-rose-600">حذف</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="rows.push({ item_id: '', qty: '', price: '' })" class="text-primary-800 font-semibold hover:text-accent-600 underline decoration-slate-300 text-sm">+ إضافة بند</button>

        <button class="ds-btn ds-btn-primary rounded-lg px-5 py-2 font-bold">حفظ الأمر</button>
    </form>
</div>
@endsection