@extends('layouts.app')

@section('title', 'مرتجع جديد')
@section('page-title', 'مرتجع على فاتورة')

@section('content')
<div class="space-y-4">
    @if($errors->any())<div class="ds-alert ds-alert-danger">{{ $errors->first() }}</div>@endif

    <div class="ds-card p-5">
        <form method="GET" action="{{ route('returns.create') }}" class="flex gap-2 items-end">
            <div class="flex-1">
                <label class="ds-label">رقم الفاتورة</label>
                <input name="sale_no" value="{{ $saleNo }}" class="ds-input" placeholder="مثال: 100001" dir="ltr">
            </div>
            <button class="ds-btn ds-btn-secondary">عرض الفاتورة</button>
        </form>
    </div>

    @if($sale)
    <form method="POST" action="{{ route('returns.store') }}" class="ds-card p-5 space-y-4">
        @csrf
        <input type="hidden" name="sale_id" value="{{ $sale->id }}">
        <div class="grid gap-3 sm:grid-cols-4 text-sm">
            <div><div class="text-slate-500">فاتورة</div><div class="font-bold">#{{ $sale->no }}</div></div>
            <div><div class="text-slate-500">التاريخ</div><div>{{ $sale->at?->format('Y-m-d H:i') }}</div></div>
            <div><div class="text-slate-500">الفرع</div><div>{{ $sale->location_id }}</div></div>
            <div><div class="text-slate-500">الإجمالي</div><div class="font-bold">{{ number_format((float)$sale->total,2) }} ج</div></div>
        </div>

        <div class="ds-table-wrap">
            <table class="ds-table">
                <thead><tr><th>إرجاع</th><th>الصنف</th><th>الطبق/UID</th><th>الكمية</th><th>السعر</th><th>الوجهة</th><th>موقع الإرجاع</th></tr></thead>
                <tbody>
                @foreach($sale->lines as $i => $line)
                    <tr>
                        <td><input type="checkbox" name="items[{{ $i }}][selected]" value="1" onchange="this.closest('tr').querySelectorAll('.ret-input').forEach(e=>e.disabled=!this.checked)"></td>
                        <td>{{ $line->item?->name }}<input type="hidden" class="ret-input" name="items[{{ $i }}][sale_line_id]" value="{{ $line->id }}" disabled></td>
                        <td class="font-mono text-xs">{{ $line->box_sn ? (\App\Modules\Inventory\Models\Box::find($line->box_sn)?->uid ?: $line->box_sn) : '—' }}</td>
                        <td>{{ $line->qty }}</td>
                        <td>{{ number_format((float)$line->total,2) }}</td>
                        <td>
                            <select name="items[{{ $i }}][destination]" class="ds-input ret-input" disabled>
                                <option value="stock">مخزون عادي</option>
                                <option value="waste">مخزن الهالك</option>
                                <option value="donation">مخزن التبرعات</option>
                            </select>
                        </td>
                        <td>
                            <select name="items[{{ $i }}][location_id]" class="ds-input ret-input" disabled>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" @selected($loc->id==$sale->location_id)>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div>
            <label class="ds-label">سبب الإرجاع</label>
            <textarea name="reason" class="ds-input" rows="2">{{ old('reason') }}</textarea>
        </div>
        <div class="flex gap-2">
            <button class="ds-btn ds-btn-primary">تسجيل المرتجع</button>
            <a href="{{ route('returns.index') }}" class="ds-btn ds-btn-secondary">إلغاء</a>
        </div>
    </form>
    @elseif($saleNo)
        <div class="ds-alert ds-alert-danger">لم يتم العثور على الفاتورة رقم {{ $saleNo }}.</div>
    @endif
</div>
<script>
// remove unselected items before submit
document.querySelectorAll('form[action="{{ route('returns.store') }}"]').forEach(f=>{
  f.addEventListener('submit',()=>{
    f.querySelectorAll('tbody tr').forEach(tr=>{
      const cb=tr.querySelector('input[type=checkbox]');
      if(cb && !cb.checked){ tr.querySelectorAll('input,select,textarea').forEach(e=>e.disabled=true); }
    });
  });
});
</script>
@endsection
