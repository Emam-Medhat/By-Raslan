@extends('layouts.app')

@section('title', 'المواقع')
@section('page-title', 'المواقع والفروع')
@section('page-subtitle', 'إدارة الفروع والمواقع ومواقع التخزين والإنتاج')

@section('page-actions')
    @can('settings.edit')
        <button type="button" class="ds-btn ds-btn-primary" @click="$dispatch('open-location-create')">
            <x-icon name="plus" size="16" />
            إضافة موقع
        </button>
    @endcan
@endsection

@section('content')
@php
    $typeMeta = [
        'shop' => ['label' => 'فرع', 'icon' => 'store', 'class' => 'ds-badge-accent'],
        'factory' => ['label' => 'مصنع', 'icon' => 'factory', 'class' => 'ds-badge-neutral'],
        'warehouse' => ['label' => 'مخزن', 'icon' => 'building-2', 'class' => 'ds-badge-neutral'],
        'section' => ['label' => 'قسم إنتاج', 'icon' => 'layers', 'class' => 'ds-badge-neutral'],
        'supplier' => ['label' => 'مورد', 'icon' => 'truck', 'class' => 'ds-badge-neutral'],
        'external' => ['label' => 'خارجي', 'icon' => 'globe', 'class' => 'ds-badge-neutral'],
        'dark' => ['label' => 'مخزن مظلم', 'icon' => 'moon', 'class' => 'ds-badge-warning'],
        'donate' => ['label' => 'تبرعات', 'icon' => 'package', 'class' => 'ds-badge-success'],
        'waste' => ['label' => 'هالك', 'icon' => 'trash-2', 'class' => 'ds-badge-danger'],
        'lost' => ['label' => 'مفقود', 'icon' => 'circle-alert', 'class' => 'ds-badge-danger'],
    ];
    $total = $locations->count();
    $active = $locations->where('active', true)->count();
    $branches = $locations->where('type', 'shop')->count();
@endphp

<div class="space-y-5" x-data="locationPage()" @open-location-create.window="openCreate()">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="ds-card p-4 flex items-center gap-4">
            <span class="ds-kpi-icon"><x-icon name="map-pin" size="20" /></span>
            <div><div class="text-xs font-semibold text-slate-500">إجمالي المواقع</div><div class="text-2xl font-extrabold text-primary-800 num">{{ $total }}</div></div>
        </div>
        <div class="ds-card p-4 flex items-center gap-4">
            <span class="ds-kpi-icon !bg-emerald-50 !text-emerald-600"><x-icon name="circle-check" size="20" /></span>
            <div><div class="text-xs font-semibold text-slate-500">المواقع المفعّلة</div><div class="text-2xl font-extrabold text-primary-800 num">{{ $active }}</div></div>
        </div>
        <div class="ds-card p-4 flex items-center gap-4">
            <span class="ds-kpi-icon !bg-accent-50 !text-accent-600"><x-icon name="store" size="20" /></span>
            <div><div class="text-xs font-semibold text-slate-500">الفروع</div><div class="text-2xl font-extrabold text-primary-800 num">{{ $branches }}</div></div>
        </div>
    </div>

    <div class="ds-table-wrap">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="ds-card-title"><x-icon name="building-2" size="18" class="text-slate-400" /> قائمة المواقع</h2>
                <p class="text-xs text-slate-500 mt-0.5">كل المواقع والفروع وأقسام الإنتاج والتخزين.</p>
            </div>
            <div class="relative w-full sm:w-72">
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="search" size="16" /></span>
                <input type="search" x-model="q" class="ds-input pr-9" placeholder="بحث بالاسم، النوع، المدينة..." autocomplete="off">
            </div>
        </div>
        <div class="ds-table-scroll">
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>الموقع</th>
                        <th>النوع</th>
                        <th>يتبع لـ</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                        <th style="width:180px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        @php
                            $meta = $typeMeta[$location->type] ?? ['label' => $location->type, 'icon' => 'map-pin', 'class' => 'ds-badge-neutral'];
                            $search = mb_strtolower($location->name.' '.$location->id.' '.($meta['label']).' '.($location->parent?->name ?? '').' '.($location->city ?? ''));
                        @endphp
                        <tr x-show="!q || @js($search).includes(q.toLowerCase())">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <x-icon :name="$meta['icon']" size="17" />
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('branches.show', $location) }}" class="font-bold text-primary-800 hover:text-accent-600 transition">{{ $location->name }}</a>
                                        <div class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $location->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="ds-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span></td>
                            <td class="text-slate-600">{{ $location->parent?->name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $location->city ?: '—' }}</td>
                            <td>
                                <span class="ds-badge {{ $location->active ? 'ds-badge-success' : 'ds-badge-danger' }}">
                                    {{ $location->active ? 'مفعّل' : 'معطّل' }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @can('settings.edit')
                                        <a href="{{ route('locations.edit', $location) }}" class="ds-btn ds-btn-sm ds-btn-secondary">تعديل</a>
                                        <form method="POST" action="{{ route('locations.toggle', $location) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="ds-btn ds-btn-sm {{ $location->active ? 'ds-btn-ghost text-amber-600 hover:bg-amber-50' : 'ds-btn-ghost text-emerald-600 hover:bg-emerald-50' }}">
                                                {{ $location->active ? 'تعطيل' : 'تفعيل' }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('users.manage')
                                        <a href="{{ route('locations.users.index', $location) }}" class="ds-btn ds-btn-sm ds-btn-ghost" title="المستخدمون">
                                            <x-icon name="users" size="15" />
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty-state icon="map-pin" title="لا توجد مواقع" description="ابدأ بإضافة أول موقع أو فرع للنظام." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Location Modal --}}
    @can('settings.edit')
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="إضافة موقع">
        <div class="absolute inset-0 bg-primary-950/45 backdrop-blur-sm" @click="createOpen = false" x-transition.opacity></div>
        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl" x-transition @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 text-accent-600"><x-icon name="map-pin" size="20" /></span>
                    <div>
                        <h2 class="text-base font-extrabold text-primary-800">إضافة موقع جديد</h2>
                        <p class="text-xs text-slate-500 mt-0.5">أضف فرعًا، مخزنًا، قسم إنتاج، أو أي موقع تشغيلي.</p>
                    </div>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition" aria-label="إغلاق">
                    <x-icon name="x" size="18" />
                </button>
            </div>

            <form method="POST" action="{{ route('locations.store') }}" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="location_create">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="loc_name" class="ds-label">اسم الموقع <span class="text-red-500">*</span></label>
                        <input id="loc_name" name="name" value="{{ old('_form') === 'location_create' ? old('name') : '' }}" class="ds-input @error('name') !border-red-400 @enderror" required placeholder="مثال: فرع المعادي">
                        @error('name') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="loc_id" class="ds-label">المعرّف (ID) <span class="text-red-500">*</span></label>
                        <input id="loc_id" name="id" value="{{ old('_form') === 'location_create' ? old('id') : '' }}" class="ds-input font-mono @error('id') !border-red-400 @enderror" dir="ltr" required placeholder="مثال: maadi-branch">
                        @error('id') <p class="ds-error">{{ $message }}</p> @enderror
                        <p class="ds-help">حروف/أرقام/شرطات بالإنجليزية، ويُستخدم في النظام.</p>
                    </div>

                    <div>
                        <label for="loc_type" class="ds-label">نوع الموقع <span class="text-red-500">*</span></label>
                        <select id="loc_type" name="type" class="ds-select @error('type') !border-red-400 @enderror" required>
                            @foreach (\App\Modules\Organization\Models\Location::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('_form') === 'location_create' && old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="loc_city" class="ds-label">المدينة</label>
                        <input id="loc_city" name="city" value="{{ old('_form') === 'location_create' ? old('city') : '' }}" class="ds-input @error('city') !border-red-400 @enderror" placeholder="مثال: القاهرة">
                        @error('city') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="loc_parent" class="ds-label">يتبع لـ (اختياري)</label>
                        <select id="loc_parent" name="parent_id" class="ds-select @error('parent_id') !border-red-400 @enderror">
                            <option value="">— بدون موقع أب —</option>
                            @foreach ($locations as $parent)
                                <option value="{{ $parent->id }}" @selected(old('_form') === 'location_create' && old('parent_id') == $parent->id)>
                                    {{ $parent->name }} ({{ \App\Modules\Organization\Models\Location::TYPES[$parent->type] ?? $parent->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id') <p class="ds-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary">إلغاء</button>
                    <button type="submit" class="ds-btn ds-btn-primary">
                        <x-icon name="plus" size="16" />
                        حفظ الموقع
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@can('settings.edit')
<script>
function locationPage() {
    return {
        q: '',
        createOpen: @json($errors->any() && old('_form') === 'location_create'),
        openCreate() { this.createOpen = true; }
    }
}
</script>
@else
<script>
function locationPage() { return { q: '', createOpen: false, openCreate(){} }; }
</script>
@endcan
@endsection
