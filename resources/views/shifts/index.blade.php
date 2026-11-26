@extends('layouts.app')

@section('title', 'إدارة الشيفتات')
@section('page-title', 'إدارة الشيفتات')

@section('content')
<div class="space-y-4" x-data="{ createOpen: {{ ($errors->any() && !old('_method')) ? 'true' : 'false' }} }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">إدارة الشيفتات</h1>
            <p class="text-sm muted">متابعة جميع الشيفتات وحركة كل شيفت</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('shifts.export') }}" class="ds-btn ds-btn-secondary ds-btn-sm">⬇ تصدير</a>
            <button onclick="window.print()" class="ds-btn ds-btn-secondary ds-btn-sm">🖨 طباعة</button>
            <button type="button" @click="createOpen = true" class="ds-btn ds-btn-primary ds-btn-sm">+ فتح وردية</button>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="createOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">فتح وردية جديدة</h2>
                    <p class="text-xs text-slate-400 mt-0.5">حدد الفرع والموظف ورصيد البداية</p>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <form method="POST" action="{{ route('shifts.store') }}" class="p-5 space-y-4">
                @csrf
                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pr-5 space-y-0.5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <div>
                    <label class="ds-label">الفرع <span class="text-red-500">*</span></label>
                    <select name="location_id" class="w-full ds-input" required>
                        <option value="">اختر الفرع</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-label">الموظف <span class="text-red-500">*</span></label>
                    <select name="cashier" class="w-full ds-input" required>
                        <option value="">اختر الموظف</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('cashier') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-label">رصيد البداية <span class="text-red-500">*</span></label>
                    <input name="opening_cash" type="number" step="0.01" min="0" value="{{ old('opening_cash', 0) }}" class="w-full ds-input" required>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary px-4">إلغاء</button>
                    <button class="ds-btn ds-btn-primary px-5">فتح الوردية</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="kpis">
        <article class="ds-kpi">
            <div class="ds-kpi-label">إجمالي الشيفتات</div>
            <div class="ds-kpi-value">{{ number_format($stats['total'] ?? $shifts->total()) }}</div>
        </article>
        <article class="ds-kpi">
            <div class="ds-kpi-label">مفتوحة</div>
            <div class="ds-kpi-value" style="color: var(--good);">{{ number_format($stats['open'] ?? 0) }}</div>
        </article>
        <article class="ds-kpi">
            <div class="ds-kpi-label">مغلقة</div>
            <div class="ds-kpi-value" style="color: var(--muted);">{{ number_format($stats['closed'] ?? 0) }}</div>
        </article>
        <article class="ds-kpi">
            <div class="ds-kpi-label">إجمالي المصروفات</div>
            <div class="ds-kpi-value" style="color: var(--bad);">{{ number_format($stats['total_expenses'] ?? 0, 2) }} ج</div>
        </article>
        <article class="ds-kpi">
            <div class="ds-kpi-label">إجمالي المبيعات</div>
            <div class="ds-kpi-value">{{ number_format($stats['total_sales'] ?? 0, 2) }} ج</div>
        </article>
    </div>

    {{-- Filters --}}
    <div class="ds-card p-4">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7 items-end">
            <div>
                <label class="ds-label">بحث</label>
                <div class="relative">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 muted">🔍</span>
                    <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو رقم الشيفت..." class="ds-input pl-10">
                </div>
            </div>
            
            <div>
                <label class="ds-label">الموظف</label>
                <select name="user_id" class="ds-input">
                    <option value="">كل الموظفين</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="ds-label">الفرع</label>
                <select name="location" class="ds-input">
                    <option value="">كل الفروع</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('location') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="ds-label">الحالة</label>
                <select name="status" class="ds-input">
                    <option value="">كل الحالات</option>
                    <option value="open" @selected(request('status') === 'open')>مفتوح</option>
                    <option value="closed" @selected(request('status') === 'closed')>مغلق</option>
                    <option value="paused" @selected(request('status') === 'paused')>موقوف</option>
                </select>
            </div>

            <div>
                <label class="ds-label">من تاريخ</label>
                <input type="date" name="from" value="{{ request('from') }}" class="ds-input">
            </div>

            <div>
                <label class="ds-label">إلى تاريخ</label>
                <input type="date" name="to" value="{{ request('to') }}" class="ds-input">
            </div>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="ds-btn ds-btn-primary ds-btn-sm flex-1">تصفية</button>
                <a href="{{ route('shifts.index') }}" class="ds-btn ds-btn-secondary ds-btn-sm flex-1">إعادة تعيين</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>الفرع</th>
                    <th>بداية الشيفت</th>
                    <th>نهاية الشيفت</th>
                    <th>عدد الساعات</th>
                    <th>رصيد بداية العهدة</th>
                    <th>العهدة المتوقعة / المرحّلة</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    @php
                        $hours = '-';
                        if ($shift->opened_at && $shift->closed_at) {
                            $diff = $shift->opened_at->diff($shift->closed_at);
                            $hours = $diff->h + ($diff->days * 24) . ' س ' . $diff->i . ' د';
                        }
                        
                        $statusLabels = [
                            'open' => ['ds-badge-accent', 'مفتوح'],
                            'paused' => ['ds-badge-warning', 'موقوف'],
                            'closed' => ['ds-badge-neutral', 'مغلق'],
                        ];
                        [$statusClass, $statusLabel] = $statusLabels[$shift->status] ?? ['ds-badge-neutral', $shift->status];
                    @endphp
                    <tr>
                        <td class="font-semibold">#{{ $shift->id }}</td>
                        <td>{{ $shift->cashierUser?->name ?? '—' }}</td>
                        <td>{{ $shift->location?->name ?? $shift->location_id ?? '—' }}</td>
                        <td class="num" style="font-size: 12px;">{{ $shift->opened_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="num" style="font-size: 12px;">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : '—' }}</td>
                        <td class="num" style="font-size: 12px;">{{ $hours }}</td>
                        <td class="num">{{ number_format((float) $shift->opening_cash, 2) }}</td>
                        <td class="num">{{ $shift->expected_cash !== null ? number_format((float) $shift->expected_cash, 2) : '—' }}</td>
                        <td class="num" style="font-size: 12px;">{{ $shift->created_at?->format('Y-m-d') ?? '—' }}</td>
                        <td><span class="ds-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td>
                            <div class="flex gap-1 items-center justify-end">
                                <a href="{{ route('shifts.show', $shift) }}" class="ds-btn ds-btn-sm ds-btn-secondary px-2 py-1" title="عرض التفاصيل">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('shifts.report', $shift) }}" class="ds-btn ds-btn-sm ds-btn-secondary px-2 py-1" title="تقرير اليومية">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                                <a href="{{ route('shifts.movements', $shift) }}" class="ds-btn ds-btn-sm ds-btn-secondary px-2 py-1" title="حركة الشيفت">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </a>
                                
                                @if($shift->status === 'open')
                                    <button onclick="openCloseModal({{ $shift->id }}, 'شيفت #{{ $shift->id }}', '{{ route('shifts.close', $shift) }}')" class="ds-btn ds-btn-sm ds-btn-primary px-2 py-1" title="إغلاق الشيفت">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    </button>
                                @endif
                                
                                @if($shift->status === 'closed')
                                    <a href="{{ route('shifts.show', $shift) }}" class="ds-btn ds-btn-sm ds-btn-secondary px-2 py-1" title="طباعة إيصال الإغلاق">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                @endif
                                
                                <a href="{{ route('shifts.edit', $shift) }}" class="ds-btn ds-btn-sm ds-btn-secondary px-2 py-1" title="تعديل">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                
                                <form action="{{ route('shifts.destroy', $shift) }}" method="POST" class="delete-form" wire:ignore>
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ds-btn ds-btn-sm ds-btn-danger px-2 py-1 delete-btn" data-name="شيفت #{{ $shift->id }}" title="حذف">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="ds-empty">لا توجد شيفتات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $shifts->links() }}</div>
</div>

{{-- Close Shift Modal --}}
<div id="closeShiftModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" wire:ignore>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="bg-red-600 text-white rounded-t-2xl px-5 py-4 flex items-center gap-3">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h3 class="font-bold">إغلاق الشيفت</h3>
        </div>
        <form method="POST" action="" id="closeShiftForm" class="p-5 space-y-4">
            @csrf
            <div class="bg-slate-50 rounded-lg p-3 text-sm">
                <div class="flex justify-between"><span class="muted">الشيفت</span><span class="font-bold" id="closeShiftName"></span></div>
            </div>
            <p class="text-sm muted">سيتم إغلاق الوردية وترحيل العهدة المتوقعة تلقائياً.</p>
            <div>
                <label class="ds-label">ملاحظات (اختياري)</label>
                <textarea name="notes" rows="2" class="ds-input" placeholder="أي ملاحظات..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 ds-btn ds-btn-danger rounded-lg py-2 font-bold" style="background: var(--bad); border-color: var(--bad); color: white;">تأكيد الإغلاق</button>
                <button type="button" onclick="closeModal('closeShiftModal')" class="px-4 py-2 rounded-lg border border-slate-300">إلغاء</button>
            </div>
        </form>
    </div>
</div>

{{-- Create Shift Modal --}}
<div id="createShiftModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" wire:ignore>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="bg-primary-800 text-white rounded-t-2xl px-5 py-4 flex items-center justify-between">
            <h3 class="font-bold">إضافة شيفت جديد</h3>
            <button onclick="closeModal('createShiftModal')" class="text-white hover:text-slate-200">✕</button>
        </div>
        <form method="POST" action="{{ route('shifts.store') }}" class="p-5 space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ds-label">الموظف <span class="text-red-500">*</span></label>
                    <select name="user_id" class="ds-input" required>
                        <option value="">اختر الموظف</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-label">الفرع <span class="text-red-500">*</span></label>
                    <select name="location_id" class="ds-input" required>
                        <option value="">اختر الفرع</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="ds-label">رصيد البداية <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="opening_cash" class="ds-input" value="0">
                </div>
                <div>
                    <label class="ds-label">الكاش المتوقع</label>
                    <input type="number" step="0.01" min="0" name="expected_cash" class="ds-input">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ds-label">وقت بداية الشيفت</label>
                    <input type="datetime-local" name="opened_at" class="ds-input" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div>
                    <label class="ds-label">وقت نهاية الشيفت</label>
                    <input type="datetime-local" name="closed_at" class="ds-input">
                </div>
            </div>
            <div>
                <label class="ds-label">الحالة</label>
                <select name="status" class="ds-input">
                    <option value="open">مفتوح</option>
                    <option value="paused">موقوف</option>
                    <option value="closed">مغلق</option>
                </select>
            </div>
            <div>
                <label class="ds-label">ملاحظات</label>
                <textarea name="notes" rows="3" class="ds-input" placeholder="أدخل أي ملاحظات إضافية"></textarea>
            </div>
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('createShiftModal')" class="flex-1 ds-btn ds-btn-secondary ds-btn-lg">إلغاء</button>
                <button type="submit" class="flex-1 ds-btn ds-btn-primary ds-btn-lg">حفظ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Modal helpers
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

// Open close shift modal
function openCloseModal(shiftId, shiftName, closeUrl) {
    document.getElementById('closeShiftForm').action = closeUrl;
    document.getElementById('closeShiftName').textContent = shiftName;
    document.getElementById('closeShiftForm').querySelector('textarea[name="notes"]').value = '';
    openModal('closeShiftModal');
}

// Print shift via hidden iframe
function printShift(url) {
    const btn = event.target.closest('button');
    if (btn) {
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    }

    // Remove old iframe
    var old = document.getElementById('shift-print-iframe');
    if (old) old.parentNode.removeChild(old);

    // Create hidden iframe
    var iframe = document.createElement('iframe');
    iframe.id = 'shift-print-iframe';
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;visibility:hidden;';
    document.body.appendChild(iframe);

    iframe.onload = function() {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch(ex) {
            console.error('Print error:', ex);
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        // Cleanup
        var cleanup = function() {
            try { document.body.removeChild(iframe); } catch(ex) {}
        };
        if (iframe.contentWindow.onafterprint !== undefined) {
            iframe.contentWindow.onafterprint = cleanup;
        }
        setTimeout(cleanup, 8000);
    };

    iframe.onerror = function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        alert('حدث خطأ أثناء تحميل الوصل');
    };

    iframe.src = url;
}

// SweetAlert for delete confirmation
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.delete-btn');
    if (!btn) return;
    
    e.preventDefault();
    const form = btn.closest('.delete-form');
    const name = btn.dataset.name || 'هذا العنصر';
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: `سيتم حذف "${name}" نهائيًا`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            reverseButtons: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    } else {
        if (confirm('هل أنت متأكد من حذف ' + name + '؟')) {
            form.submit();
        }
    }
});

// Close modals on outside click
document.addEventListener('click', function(e) {
    if (e.target.id === 'closeShiftModal') closeModal('closeShiftModal');
    if (e.target.id === 'createShiftModal') closeModal('createShiftModal');
});

// ESC key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('closeShiftModal');
        closeModal('createShiftModal');
    }
});

// Listen for Alpine events if using Livewire-style
document.addEventListener('open-create-modal', function() {
    openModal('createShiftModal');
});

document.addEventListener('close-create-modal', function() {
    closeModal('createShiftModal');
});
</script>
@endpush
@endsection
