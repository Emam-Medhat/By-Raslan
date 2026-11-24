@extends('layouts.app')

@section('title', 'مسارات الإنتاج')
@section('page-title', 'مسارات الإنتاج')

@section('content')
@php
    $sectionNames = $sections->pluck('name', 'id');
    $routesPayload = $items->mapWithKeys(function ($item) use ($sectionNames) {
        $latestVer = (int) $item->routes->max('ver');
        $latest = $item->routes->where('ver', $latestVer)->sortBy('step')->values();
        return [$item->id => [
            'name' => $item->name,
            'ver' => $latestVer,
            'steps' => $latest->map(fn ($r) => [
                'id' => $r->section_id,
                'name' => $sectionNames[$r->section_id] ?? $r->section_id,
            ])->values(),
        ]];
    });
@endphp

<div class="space-y-4" x-data="routeBuilder({{ Illuminate\Support\Js::from($routesPayload) }}, {{ Illuminate\Support\Js::from($sections->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values()) }})">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-primary-800">مسارات الإنتاج</h1>
            <p class="text-sm text-slate-500 mt-0.5">حدد ترتيب غرف/أقسام التصنيع لكل صنف. يتم حفظ نسخة جديدة عند كل تعديل.</p>
        </div>
        <a href="{{ route('production.index') }}" class="ds-btn ds-btn-secondary"><x-icon name="arrow-right" size="16" class="ml-1" /> أوامر الإنتاج</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        {{-- Builder --}}
        <div class="ds-card p-5 space-y-4">
            <div>
                <label class="ds-label">الصنف</label>
                <select x-model="itemId" @change="loadItem()" class="ds-input">
                    <option value="">اختر الصنف...</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="itemId" x-cloak class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="text-sm font-bold text-primary-800">المسار الحالي</div>
                        <span class="ds-badge ds-badge-neutral" x-text="currentVer ? ('إصدار v' + currentVer) : 'لا يوجد مسار'"></span>
                    </div>
                    <template x-if="steps.length">
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-for="(s, idx) in steps" :key="s.id + '-' + idx">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold text-primary-800 shadow-sm">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-accent-100 text-accent-700 text-[11px] font-extrabold" x-text="idx + 1"></span>
                                        <span x-text="s.name"></span>
                                        <button type="button" @click="removeStep(idx)" class="text-slate-400 hover:text-rose-600 transition" title="إزالة"><x-icon name="x" size="14" /></button>
                                    </span>
                                    <span x-show="idx < steps.length - 1" class="text-slate-300"><x-icon name="chevron-left" size="14" /></span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="!steps.length" class="text-sm text-slate-400">لم يتم تحديد غرف بعد. اختر الغرف بالترتيب من القائمة أدناه.</div>
                </div>

                <div>
                    <div class="text-sm font-bold text-primary-800 mb-2">إضافة غرفة للمسار</div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="sec in sections" :key="sec.id">
                            <button type="button" @click="addStep(sec)"
                                    class="flex items-center justify-between rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                                    :class="isInSteps(sec.id) ? 'border-accent-300 bg-accent-50 text-accent-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'">
                                <span class="flex items-center gap-2">
                                    <x-icon name="store" size="14" class="opacity-70" />
                                    <span x-text="sec.name"></span>
                                </span>
                                <span x-show="isInSteps(sec.id)" class="text-[11px] font-extrabold" x-text="'#' + (stepIndex(sec.id)+1)"></span>
                                <span x-show="!isInSteps(sec.id)" class="opacity-50"><x-icon name="plus" size="14" /></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2"><x-icon name="info" size="16" class="ml-1" /> اضغط على الغرف بالترتيب. الضغط مرة أخرى يضيفها مجددًا إذا احتجت نفس الغرفة في مرحلة لاحقة.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                    <form method="POST" :action="actionUrl()" class="inline-flex" @submit="return confirmSave()">
                        @csrf
                        <template x-for="(s, idx) in steps" :key="'f-'+idx+'-'+s.id">
                            <input type="hidden" name="sections[]" :value="s.id">
                        </template>
                        <button type="submit" class="ds-btn ds-btn-primary px-5" :disabled="!steps.length">
                            <x-icon name="save" size="16" class="ml-1" /> حفظ المسار
                        </button>
                    </form>
                    <button type="button" @click="steps=[]" class="ds-btn ds-btn-secondary px-4" :disabled="!steps.length">مسح</button>
                    <button type="button" @click="loadItem()" class="ds-btn ds-btn-secondary px-4" :disabled="!itemId">استعادة الحالي</button>
                </div>
            </div>

            <div x-show="!itemId" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-400 text-sm">
                <x-icon name="git-branch" size="24" class="mb-2 block text-slate-300" />
                اختر صنفًا لعرض وتعديل مسار الإنتاج الخاص به.
            </div>
        </div>

        {{-- Current routes list --}}
        <div class="ds-card overflow-hidden">
            <div class="ds-card-header">
                <h2 class="ds-card-title">المسارات الحالية</h2>
                <span class="ds-badge ds-badge-neutral">{{ $items->filter(fn($i)=>$i->routes->max('ver') > 0)->count() }} صنف</span>
            </div>
            <div class="max-h-[70vh] overflow-y-auto divide-y divide-slate-100">
                @forelse($items as $item)
                    @php
                        $latestVer = (int) $item->routes->max('ver');
                        $latest = $item->routes->where('ver', $latestVer)->sortBy('step')->values();
                    @endphp
                    <div class="px-4 py-3 hover:bg-slate-50/60 transition">
                        <div class="flex items-center justify-between gap-2">
                            <button type="button" @click="itemId='{{ $item->id }}'; loadItem()" class="text-right min-w-0">
                                <div class="font-semibold text-primary-800 truncate {{ $latestVer ? '' : 'text-slate-400' }}">{{ $item->name }}</div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $latestVer ? 'v'.$latestVer.' · '.$latest->count().' خطوة' : 'بدون مسار' }}
                                </div>
                            </button>
                            @if($latestVer)
                                <span class="ds-badge ds-badge-success">جاهز</span>
                            @else
                                <span class="ds-badge ds-badge-warning">غير محدد</span>
                            @endif
                        </div>
                        @if($latestVer)
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @foreach($latest as $idx => $r)
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $idx+1 }}. {{ $sectionNames[$r->section_id] ?? $r->section_id }}</span>
                                    @if(!$loop->last)<x-icon name="chevron-left" size="16" class="text-slate-300" />@endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="ds-empty">لا توجد أصناف</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function routeBuilder(routes, sections) {
    return {
        routes, sections,
        itemId: '',
        steps: [],
        currentVer: 0,
        loadItem() {
            const r = this.routes[String(this.itemId)];
            if (!r) { this.steps = []; this.currentVer = 0; return; }
            this.steps = r.steps.map(s => ({...s}));
            this.currentVer = r.ver;
        },
        addStep(sec) { this.steps.push({ id: sec.id, name: sec.name }); },
        removeStep(idx) { this.steps.splice(idx, 1); },
        isInSteps(id) { return this.steps.some(s => s.id === id); },
        stepIndex(id) { return this.steps.findIndex(s => s.id === id); },
        actionUrl() {
            const base = {{ Illuminate\Support\Js::from(route('production.setRoute', ['item' => '__ID__'])) }};
            return base.replace('__ID__', this.itemId || '0');
        },
        confirmSave() {
            if (!this.itemId) { alert('اختر الصنف أولاً'); return false; }
            if (!this.steps.length) { alert('أضف غرفة واحدة على الأقل'); return false; }
            return true;
        }
    }
}
</script>
@endpush
@endsection
