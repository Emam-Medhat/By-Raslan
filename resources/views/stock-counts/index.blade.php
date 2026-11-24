@extends('layouts.app')

@section('title', 'الجرد')
@section('page-title', 'جرد المخزون')

@section('content')
<div class="space-y-4" x-data="{ createOpen: {{ ($errors->any() || request()->boolean('create')) ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">جرد المخزون</h1>
        <button type="button" @click="createOpen = true" class="ds-btn ds-btn-primary px-4 py-2 text-sm font-bold">+ جرد جديد</button>
    </div>

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
                    <h2 class="text-base font-extrabold text-primary-800">جرد جديد</h2>
                    <p class="text-xs text-slate-400 mt-0.5">أنشئ جلسة جرد لموقع محدد</p>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <form method="POST" action="{{ route('stock-counts.store') }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="ds-label">الفرع / الموقع</label>
                    <select name="location_id" class="w-full ds-input @error('location_id') !border-red-400 @enderror" required>
                        <option value="">اختر الموقع</option>
                        @foreach(($createLocations ?? $locations) as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="ds-label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full ds-input @error('notes') !border-red-400 @enderror" placeholder="أي ملاحظات...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary px-4">إلغاء</button>
                    <button class="ds-btn ds-btn-primary px-5">إنشاء الجرد</button>
                </div>
            </form>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 grid gap-3 sm:grid-cols-4">
        <select name="location" class="ds-input">
            <option value="">كل الفروع</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" @selected(request('location') === $loc->id)>{{ $loc->name }}</option>
            @endforeach
        </select>
        <select name="status" class="ds-input">
            <option value="">كل الحالات</option>
            <option value="draft" @selected(request('status') === 'draft')>مسودة</option>
            <option value="in_progress" @selected(request('status') === 'in_progress')>جاري</option>
            <option value="completed" @selected(request('status') === 'completed')>مكتمل</option>
            <option value="approved" @selected(request('status') === 'approved')>معتمد</option>
        </select>
        <button class="ds-btn ds-btn-primary px-4 py-2 text-sm font-bold">تصفية</button>
        <a href="{{ route('stock-counts.index') }}" class="ds-btn ds-btn-secondary px-3 py-2 text-sm text-center">إعادة ضبط</a>
    </form>

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>رقم الجرد</th>
                    <th>الفرع</th>
                    <th>المستخدم</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($counts as $count)
                    <tr>
                        <td>#{{ $count->id }}</td>
                        <td class="font-semibold">{{ $count->count_number }}</td>
                        <td>{{ $count->location?->name }}</td>
                        <td>{{ $count->user?->name }}</td>
                        <td>
                            <span class="ds-badge {{ $count->status === 'approved' ? 'ds-badge-success' : ($count->status === 'completed' ? 'ds-badge-primary' : ($count->status === 'in_progress' ? 'ds-badge-warning' : 'ds-badge-neutral')) }}">
                                {{ $count->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="text-xs">{{ $count->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('stock-counts.show', $count) }}" class="ds-link text-xs">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ds-empty">لا توجد عمليات جرد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">{{ $counts->links() }}</div>
</div>
@endsection