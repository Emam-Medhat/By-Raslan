@extends('layouts.app')

@section('title', 'المصروفات')
@section('page-title', 'المصروفات')

@section('page-actions')
    <div class="flex items-center gap-2">
        <button type="button" class="ds-btn ds-btn-secondary" onclick="document.getElementById('expense-filter-modal').showModal()">
            <x-icon name="filter" size="16" /> فلتر
        </button>
        @can('acc.post')
            <button type="button" class="ds-btn ds-btn-primary" onclick="document.getElementById('expense-create-modal').showModal()">
                <x-icon name="plus" size="16" /> إضافة مصروف
            </button>
        @endcan
    </div>
@endsection

@section('content')
@php
    $filters = $filters ?? request()->only(['status','category_id','location_id','date_from','date_to','q']);
    $activeFilters = collect($filters)->filter(fn($v) => filled($v))->count();
@endphp
<div class="space-y-4">
    @if($activeFilters)
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="ds-badge ds-badge-neutral">فلاتر نشطة: {{ $activeFilters }}</span>
            <a href="{{ route('expenses.index') }}" class="text-accent-600 hover:underline">مسح الفلاتر</a>
        </div>
    @endif

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>التاريخ</th>
                    <th>البند</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td>{{ $expense->no }}</td>
                        <td class="num" style="font-size: 12px;">{{ $expense->day }}</td>
                        <td>{{ $expense->category?->name }}</td>
                        <td class="num font-bold">{{ number_format($expense->amount, 2) }}</td>
                        <td>
                            <span class="ds-badge {{ 
                                $expense->status === 'ok' ? 'ds-badge-success' : 
                                ($expense->status === 'pending' ? 'ds-badge-warning' : 'ds-badge-danger') 
                            }}">
                                {{ ['ok' => 'مقبول', 'pending' => 'مستني', 'rejected' => 'مرفوض'][$expense->status] }}
                            </span>
                        </td>
                        <td>
                            @can('acc.approve')
                                @if ($expense->status === 'pending')
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="approve" value="1">
                                        <button class="ds-btn ds-btn-sm ds-btn-success">موافقة</button>
                                    </form>
                                    <form method="POST" action="{{ route('expenses.decide', $expense) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="approve" value="0">
                                        <button class="ds-btn ds-btn-sm ds-btn-danger">رفض</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ds-empty">لا توجد مصروفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $expenses->links() }}</div>
</div>

<dialog id="expense-filter-modal" class="fixed inset-0 m-auto rounded-2xl p-0 w-full max-w-2xl bg-transparent backdrop:bg-black/40" style="border:none; max-height:calc(100vh - 2rem); overflow:visible;">
    <form method="GET" action="{{ route('expenses.index') }}" class="ds-card p-0 overflow-hidden bg-white" style="max-height:calc(100vh - 2rem); overflow:auto;">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-primary-800">فلترة المصروفات</h3>
            <button type="button" class="ds-btn ds-btn-sm ds-btn-ghost" onclick="this.closest('dialog').close()"><x-icon name="x" size="16" /></button>
        </div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ds-label">الحالة</label>
                <select name="status" class="ds-input">
                    <option value="">الكل</option>
                    <option value="pending" @selected(($filters['status'] ?? '')==='pending')>مستني</option>
                    <option value="ok" @selected(($filters['status'] ?? '')==='ok')>مقبول</option>
                    <option value="rejected" @selected(($filters['status'] ?? '')==='rejected')>مرفوض</option>
                </select>
            </div>
            <div>
                <label class="ds-label">البند</label>
                <select name="category_id" class="ds-input">
                    <option value="">الكل</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '')===(string)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-label">الموقع</label>
                <select name="location_id" class="ds-input">
                    <option value="">الكل</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string)($filters['location_id'] ?? '')===(string)$location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-label">من تاريخ</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="ds-input">
            </div>
            <div>
                <label class="ds-label">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="ds-input">
            </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <a href="{{ route('expenses.index') }}" class="ds-btn ds-btn-secondary">مسح</a>
            <button class="ds-btn ds-btn-primary">تطبيق</button>
        </div>
    </form>
</dialog>

@can('acc.post')
<dialog id="expense-create-modal" class="fixed inset-0 m-auto rounded-2xl p-0 w-full max-w-2xl bg-transparent backdrop:bg-black/40" style="border:none; max-height:calc(100vh - 2rem); overflow:visible;">
    <form method="POST" action="{{ route('expenses.store') }}" class="ds-card p-0 overflow-hidden bg-white" style="max-height:calc(100vh - 2rem); overflow:auto;">
        @csrf
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-primary-800">إضافة مصروف</h3>
            <button type="button" class="ds-btn ds-btn-sm ds-btn-ghost" onclick="this.closest('dialog').close()"><x-icon name="x" size="16" /></button>
        </div>
        <div class="p-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="ds-label">الموقع</label>
                <select name="location_id" class="ds-input" required>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-label">البند</label>
                <select name="category_id" class="ds-input" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ds-label">المبلغ</label>
                <input name="amount" type="number" step="0.01" min="0.01" class="ds-input" required>
            </div>
            <div>
                <label class="ds-label">الدفع من</label>
                <select name="paid_from" class="ds-input">
                    <option value="cash">نقدي</option>
                    <option value="bank">بنك</option>
                    <option value="shift">من الدرج</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="ds-label">ملاحظة</label>
                <input name="note" class="ds-input">
            </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" class="ds-btn ds-btn-secondary" onclick="this.closest('dialog').close()">إلغاء</button>
            <button class="ds-btn ds-btn-primary">حفظ المصروف</button>
        </div>
    </form>
</dialog>
@endcan

@if($errors->any())
<script>document.getElementById('expense-create-modal')?.showModal();</script>
@endif
@endsection
