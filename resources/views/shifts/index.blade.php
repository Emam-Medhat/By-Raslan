@extends('layouts.app')

@section('title', 'إدارة الشيفتات')
@section('page-title', 'إدارة الشيفتات')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold">إدارة الشيفتات</h1>
        <p class="text-sm text-slate-500">متابعة جميع الشيفتات وحركة كل شيفت</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('shifts.export') }}" class="bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm font-bold hover:bg-slate-50">⬇ تصدير</a>
        <button onclick="window.print()" class="bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm font-bold hover:bg-slate-50">🖨 طباعة</button>
    </div>
</div>

<div class="space-y-4">
    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <input name="q" value="{{ request('q') }}" placeholder="بحث: اسم / رقم الشيفت" class="rounded-lg border border-slate-300 px-3 py-2">
        <select name="branch" class="rounded-lg border border-slate-300 px-3 py-2">
            <option value="">كل الفروع</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch') === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
            <option value="">كل الحالات</option>
            <option value="open" @selected(request('status') === 'open')>مفتوح</option>
            <option value="closed" @selected(request('status') === 'closed')>مغلق</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-slate-300 px-3 py-2">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-slate-300 px-3 py-2">
        <div class="flex gap-2">
            <button class="bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 text-sm font-bold">تصفية</button>
            <a href="{{ route('shifts.index') }}" class="bg-slate-100 rounded-lg px-3 py-2 text-sm">إعادة ضبط</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-right px-3 py-3 font-semibold">رقم الشيفت</th>
                    <th class="text-right px-3 py-3 font-semibold">الموظف</th>
                    <th class="text-right px-3 py-3 font-semibold">الفرع</th>
                    <th class="text-right px-3 py-3 font-semibold">الكاشير/الجهاز</th>
                    <th class="text-right px-3 py-3 font-semibold">البداية</th>
                    <th class="text-right px-3 py-3 font-semibold">النهاية</th>
                    <th class="text-right px-3 py-3 font-semibold">الساعات</th>
                    <th class="text-right px-3 py-3 font-semibold">رصيد البداية</th>
                    <th class="text-right px-3 py-3 font-semibold">المتوقع</th>
                    <th class="text-right px-3 py-3 font-semibold">المعدود</th>
                    <th class="text-right px-3 py-3 font-semibold">الحالة</th>
                    <th class="text-right px-3 py-3 font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shifts as $shift)
                    <tr class="hover:bg-slate-50 even:bg-slate-50/50">
                        <td class="px-3 py-3 font-semibold">#{{ $shift->id }}</td>
                        <td class="px-3 py-3">{{ $shift->cashierUser?->name }}</td>
                        <td class="px-3 py-3">{{ $shift->location?->name ?? $shift->location_id }}</td>
                        <td class="px-3 py-3 text-slate-500">{{ $shift->pos_terminal ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs">{{ optional($shift->opened_at)->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-3 text-xs">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-3 py-3">{{ $shift->status === 'open' ? number_format($shift->workingHours(), 1).' س' : ($shift->opened_at && $shift->closed_at ? number_format($shift->workingHours(), 1).' س' : '—') }}</td>
                        <td class="px-3 py-3">{{ number_format((float) $shift->opening_cash, 2) }}</td>
                        <td class="px-3 py-3">{{ $shift->expected_cash !== null ? number_format((float) $shift->expected_cash, 2) : '—' }}</td>
                        <td class="px-3 py-3">{{ $shift->counted_cash !== null ? number_format((float) $shift->counted_cash, 2) : '—' }}</td>
                        <td class="px-3 py-3">
                            <span class="text-xs rounded px-2 py-0.5 {{ $shift->status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $shift->status === 'open' ? 'مفتوح' : 'مغلق' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ route('shifts.movements', $shift) }}" class="text-xs text-brand-700 underline" title="كشف حركة الشيفت">📊 حركة</a>
                            @if ($shift->status === 'open')
                                <a href="{{ route('pos.index') }}" class="text-xs text-rose-600 underline mr-2">إغلاق</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-3 py-8 text-center text-slate-400">لا توجد شيفتات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $shifts->links() }}</div>
</div>
@endsection