# SHIFT PAGE UI/UX COMPARISON — مقارنة صفحة الوردية

## Nano City Shift Page

### Layout Structure
```
┌─────────────────────────────────────────────────────────────┐
│ Header: "قائمة الشيفتات" + [إضافة شيفت] + [بحث] + [فلاتر]  │
├─────────────────────────────────────────────────────────────┤
│ Stats Cards: [إجمالي] [نشط] [مغلق] [الفروقات]              │
├─────────────────────────────────────────────────────────────┤
│ Filters: [بحث] [موظف] [فرع] [حالة] [من] [إلى] [إعادة ضبط] │
├─────────────────────────────────────────────────────────────┤
│ Table: # | موظف | فرع | بداية | نهاية | ساعات | رصيد بداية  │
│        | رصيد نهاية | رصيد حقيقي | تاريخ | حالة | إجراءات    │
├─────────────────────────────────────────────────────────────┤
│ Pagination                                                   │
├─────────────────────────────────────────────────────────────┤
│ Modals: [إضافة شيفت] [إغلاق شيفت]                          │
└─────────────────────────────────────────────────────────────┘
```

### Key Features
1. **Stats Cards** (4 cards):
   - إجمالي الشيفتات
   - نشط
   - مغلق
   - الفروقات

2. **Advanced Filters**:
   - بحث (اسم / رقم الشيفت / الكاشير)
   - فلتر موظف
   - فلتر فرع
   - فلتر حالة
   - تاريخ من/إلى
   - إعادة ضبط

3. **Table Columns**:
   - # (رقم)
   - الموظف
   - المتجر/الفرع
   - بداية الشيفت
   - نهاية الشيفت
   - عدد الساعات
   - رصيد بداية الدرج
   - رصيد نهاية الدرج
   - الرصيد الحقيقي (بداية + مبيعات)
   - التاريخ
   - الحالة
   - الإجراءات

4. **Status Indicators**:
   - نشط (أخضر)
   - موقوف (أصفر)
   - مغلق (رمادي)

5. **Difference Display**:
   - عجز (أحمر)
   - زيادة (أصفر)
   - مطابق (أخضر)

6. **Actions per Row**:
   - إغلاق (إذا مفتوح)
   - طباعة (إذا مغلق)
   - عرض
   - تعديل
   - حذف

7. **Modals**:
   - إضافة شيفت (Create Shift)
   - إغلاق شيفت (Close Shift)

8. **Responsive**:
   - Desktop: جدول كامل
   - Mobile: بطاقات

## BY RASLAN Shift Page (Current)

### Layout Structure
```
┌─────────────────────────────────────────────────────────────┐
│ Header: "إدارة الشيفتات" + [تصدير] + [طباعة]                │
├─────────────────────────────────────────────────────────────┤
│ Filters: [بحث] [فرع] [حالة] [من] [إلى] [تصفية]            │
├─────────────────────────────────────────────────────────────┤
│ Table: # | موظف | فرع | كاشير/جهاز | بداية | نهاية | ساعات │
│        | رصيد بداية | متوقع | معدود | حالة | إجراءات        │
├─────────────────────────────────────────────────────────────┤
│ Pagination                                                   │
└─────────────────────────────────────────────────────────────┘
```

### Missing Features
1. ❌ No stats cards
2. ❌ No advanced filters (موظف، بحث متقدم)
3. ❌ No color-coded status badges
4. ❌ No difference display (عجز/زيادة/مطابق)
5. ❌ No create shift modal
6. ❌ No close shift modal
7. ❌ No print closing receipt
8. ❌ No responsive mobile view
9. ❌ No real balance calculation (بداية + مبيعات)

## Implementation Plan

### New `shifts/index.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="space-y-4">
    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($stats as $stat)
            <div class="bg-white rounded-xl border p-5">
                <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                <div class="text-2xl font-extrabold mt-1">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <input name="q" value="{{ request('q') }}" placeholder="بحث..." class="rounded-lg border px-3 py-2">
        <select name="location" class="rounded-lg border px-3 py-2">
            <option value="">كل الفروع</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" @selected(request('location') === $loc->id)>{{ $loc->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border px-3 py-2">
            <option value="">كل الحالات</option>
            <option value="open" @selected(request('status') === 'open')>مفتوح</option>
            <option value="closed" @selected(request('status') === 'closed')>مغلق</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border px-3 py-2">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border px-3 py-2">
        <button class="bg-brand-600 text-white rounded-lg px-4 py-2">تصفية</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-right px-3 py-3">#</th>
                    <th class="text-right px-3 py-3">الموظف</th>
                    <th class="text-right px-3 py-3">الفرع</th>
                    <th class="text-right px-3 py-3">البداية</th>
                    <th class="text-right px-3 py-3">النهاية</th>
                    <th class="text-right px-3 py-3">رصيد البداية</th>
                    <th class="text-right px-3 py-3">المتوقع</th>
                    <th class="text-right px-3 py-3">المعدود</th>
                    <th class="text-right px-3 py-3">الفرق</th>
                    <th class="text-right px-3 py-3">الحالة</th>
                    <th class="text-right px-3 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($shifts as $shift)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-3 font-semibold">#{{ $shift->id }}</td>
                        <td class="px-3 py-3">{{ $shift->cashierUser?->name }}</td>
                        <td class="px-3 py-3">{{ $shift->location?->name }}</td>
                        <td class="px-3 py-3 text-xs">{{ $shift->opened_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-3 text-xs">{{ $shift->closed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-3">{{ number_format($shift->opening_cash, 2) }}</td>
                        <td class="px-3 py-3">{{ $shift->expected_cash !== null ? number_format($shift->expected_cash, 2) : '—' }}</td>
                        <td class="px-3 py-3">{{ $shift->counted_cash !== null ? number_format($shift->counted_cash, 2) : '—' }}</td>
                        <td class="px-3 py-3">
                            @if($shift->counted_cash !== null && $shift->expected_cash !== null)
                                @php($diff = $shift->counted_cash - $shift->expected_cash)
                                <span class="text-xs rounded px-2 py-0.5 {{ $diff < 0 ? 'bg-rose-100 text-rose-700' : ($diff > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ number_format($diff, 2) }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <span class="text-xs rounded px-2 py-0.5 {{ $shift->status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $shift->status === 'open' ? 'مفتوح' : 'مغلق' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('shifts.movements', $shift) }}" class="text-xs text-brand-700 underline">حركة</a>
                            @if($shift->status === 'open')
                                <button onclick="closeShift({{ $shift->id }})" class="text-xs text-rose-600 underline mr-2">إغلاق</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-3 py-8 text-center text-slate-400">لا توجد شيفتات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $shifts->links() }}</div>
</div>

{{-- Close Shift Modal --}}
<div id="closeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="bg-rose-600 text-white px-5 py-4 flex items-center gap-3">
            <span class="text-xl">🔒</span>
            <h3 class="font-bold">إغلاق الشيفت</h3>
        </div>
        <form method="POST" action="" id="closeForm" class="p-5 space-y-4">
            @csrf
            <p class="text-sm text-slate-500">أدخل النقد المعدود الفعلي في نهاية الشيفت.</p>
            <div>
                <label class="block text-sm font-semibold mb-1">الرصيد المعدود</label>
                <input name="counted" type="number" step="0.01" min="0" required class="w-full rounded-lg border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ملاحظات (اختياري)</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-rose-600 text-white rounded-lg py-2 font-bold">تأكيد الإغلاق</button>
                <button type="button" onclick="closeModal.classList.add('hidden')" class="px-4 py-2 rounded-lg border">إلغاء</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function closeShift(id) {
    const modal = document.getElementById('closeModal');
    const form = document.getElementById('closeForm');
    form.action = '{{ route('shifts.close', ':id') }}'.replace(':id', id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
</script>
@endpush
@endsection
```