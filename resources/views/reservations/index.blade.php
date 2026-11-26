@extends('layouts.app')

@section('title', 'الحجوزات')
@section('page-title', 'الحجوزات')

@section('content')
<div class="space-y-4">
    @if(session('status'))<div class="ds-alert ds-alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="ds-alert ds-alert-danger">{{ $errors->first() }}</div>@endif

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="ds-card p-5">
            <h3 class="font-bold mb-3">حجز جديد</h3>
            <form method="POST" action="{{ route('reservations.store') }}" class="space-y-3" x-data="{lines:[{item_id:'',qty:1,note:''}]}">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="ds-label">الفرع</label><select name="location_id" class="ds-input" required>@foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
                    <div><label class="ds-label">تاريخ التنفيذ</label><input type="date" name="scheduled_for" value="{{ old('scheduled_for', now()->addDay()->toDateString()) }}" class="ds-input" required></div>
                    <div><label class="ds-label">اسم العميل</label><input name="customer_name" value="{{ old('customer_name') }}" class="ds-input"></div>
                    <div><label class="ds-label">هاتف العميل</label><input name="customer_phone" value="{{ old('customer_phone') }}" class="ds-input" dir="ltr"></div>
                </div>
                <div><label class="ds-label">العنوان</label><input name="customer_address" value="{{ old('customer_address') }}" class="ds-input"></div>
                <div><label class="ds-label">ملاحظات</label><textarea name="notes" class="ds-input" rows="2">{{ old('notes') }}</textarea></div>
                <div>
                    <div class="flex items-center justify-between mb-2"><label class="ds-label mb-0">الأصناف</label><button type="button" class="ds-btn ds-btn-sm ds-btn-secondary" @click="lines.push({item_id:'',qty:1,note:''})">+ صنف</button></div>
                    <template x-for="(line, i) in lines" :key="i">
                        <div class="grid gap-2 sm:grid-cols-12 mb-2">
                            <div class="sm:col-span-6"><select class="ds-input" :name="`lines[${i}][item_id]`" x-model="line.item_id" required><option value="">اختر الصنف</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                            <div class="sm:col-span-2"><input class="ds-input" type="number" step="0.001" min="0.001" :name="`lines[${i}][qty]`" x-model="line.qty" placeholder="كمية" required></div>
                            <div class="sm:col-span-3"><input class="ds-input" :name="`lines[${i}][note]`" x-model="line.note" placeholder="ملاحظة"></div>
                            <div class="sm:col-span-1"><button type="button" class="ds-btn ds-btn-sm ds-btn-danger w-full" @click="if(lines.length>1)lines.splice(i,1)">×</button></div>
                        </div>
                    </template>
                </div>
                <button class="ds-btn ds-btn-primary">حفظ الحجز</button>
            </form>
        </div>

        <div class="ds-card p-5">
            <h3 class="font-bold mb-3">تقرير تجهيز المصنع لليوم التالي</h3>
            <form method="GET" action="{{ route('reservations.index') }}" class="flex gap-2 mb-3">
                <input type="hidden" name="date" value="{{ $date }}">
                <input type="date" name="report_date" value="{{ $reportDate }}" class="ds-input">
                <button class="ds-btn ds-btn-secondary">عرض</button>
            </form>
            <div class="ds-table-wrap">
                <table class="ds-table"><thead><tr><th>الصنف</th><th>إجمالي الكمية</th><th>الوحدة</th></tr></thead><tbody>
                @forelse($reportRows as $r)
                    <tr><td>{{ $r['item'] }}</td><td class="num font-bold">{{ number_format($r['qty'],3) }}</td><td>{{ $r['unit'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="ds-empty">لا توجد حجوزات لهذا التاريخ</td></tr>
                @endforelse
                </tbody></table>
            </div>
            @if($reportNotes->isNotEmpty())
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <h4 class="text-sm font-bold text-amber-900">ملاحظات الحجوزات</h4>
                    <ul class="mt-2 space-y-1 text-sm text-amber-900">
                        @foreach($reportNotes as $entry)
                            <li><span class="font-semibold">حجز #{{ $entry['no'] }}@if($entry['customer']) — {{ $entry['customer'] }}@endif:</span> {{ $entry['note'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="ds-card p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">حجوزات يوم {{ $date }}</h3>
            <form method="GET" action="{{ route('reservations.index') }}" class="flex gap-2"><input type="date" name="date" value="{{ $date }}" class="ds-input"><button class="ds-btn ds-btn-secondary">عرض</button></form>
        </div>
        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>#</th><th>العميل</th><th>الفرع</th><th>التنفيذ</th><th>الحالة</th><th>الأصناف</th><th>إجراء</th></tr></thead>
                <tbody>
                @forelse($reservations as $r)
                    <tr>
                        <td>{{ $r->no }}</td>
                        <td><div>{{ $r->customer_name }}</div><div class="text-xs text-slate-500">{{ $r->customer_phone }}</div></td>
                        <td>{{ $r->location?->name }}</td>
                        <td>{{ $r->scheduled_for?->format('Y-m-d') }}</td>
                        <td><span class="ds-badge {{ $r->status==='converted'?'ds-badge-success':($r->status==='cancelled'?'ds-badge-danger':'ds-badge-warning') }}">{{ $r->status }}</span></td>
                        <td class="text-xs">@foreach($r->lines as $l)<div>{{ $l->item?->name }} — {{ $l->qty }}</div>@endforeach</td>
                        <td class="space-x-1 space-x-reverse">
                            @if(in_array($r->status,['pending','confirmed']))
                                <form method="POST" action="{{ route('reservations.confirm',$r) }}" class="inline">@csrf<button class="ds-btn ds-btn-sm ds-btn-secondary">تأكيد</button></form>
                                <form method="POST" action="{{ route('reservations.convert',$r) }}" class="inline">@csrf<button class="ds-btn ds-btn-sm ds-btn-primary">تحويل لأوردر</button></form>
                                <form method="POST" action="{{ route('reservations.cancel',$r) }}" class="inline">@csrf<button class="ds-btn ds-btn-sm ds-btn-danger">إلغاء</button></form>
                            @elseif($r->status==='converted' && $r->online_order_id)
                                <a href="{{ route('orders.show',$r->online_order_id) }}" class="ds-btn ds-btn-sm ds-btn-secondary">عرض الأوردر</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ds-empty">لا توجد حجوزات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
