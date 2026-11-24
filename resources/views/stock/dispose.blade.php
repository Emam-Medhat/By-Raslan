@extends('layouts.app')

@section('title', 'هالك وتبرعات')
@section('page-title', 'هالك وتبرعات')
@section('page-subtitle', 'تسجيل خروج العلب كهالك أو تبرعات مع توثيق السبب والموقع')

@section('content')
@php
    $user = auth()->user();
    $canWaste = $user && ($user->isOwner() || $user->hasPermission('stock.waste'));
    $canDonate = $user && ($user->isOwner() || $user->hasPermission('stock.donate'));
    $canDispose = $canWaste || $canDonate;
    $oldKind = old('kind', $canWaste ? 'waste' : 'donate');
@endphp

<div class="max-w-5xl space-y-5" x-data="{
    kind: @js($oldKind),
    boxes: @js(old('boxes', '')),
    get boxCount() {
        if (!this.boxes) return 0;
        return this.boxes.split(/[\s,،]+/).map(s => s.trim()).filter(Boolean).length;
    }
}">
    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="ds-card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <x-icon name="trash-2" size="20" />
                        </span>
                        <div>
                            <h2 class="text-base font-extrabold text-primary-800">تنفيذ عملية هالك / تبرعات</h2>
                            <p class="text-xs text-slate-500 mt-0.5">أدخل أرقام العلب كما هي مسجلة بالنظام. سيتم التحقق من توفرها في الموقع المحدد.</p>
                        </div>
                    </div>
                </div>

                @unless($canDispose)
                    <div class="p-5">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-3">
                            <span class="mt-0.5 text-amber-500 shrink-0"><x-icon name="circle-alert" size="18" /></span>
                            <div>
                                <div class="font-bold">لا تملك صلاحية تنفيذ هذه العملية</div>
                                <div class="text-amber-700 mt-0.5">تحتاج صلاحية <span class="font-semibold">stock.waste</span> للهالك أو <span class="font-semibold">stock.donate</span> للتبرعات.</div>
                            </div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('stock.dispose.store') }}" class="p-5 space-y-5">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="location_id" class="ds-label">الموقع <span class="text-red-500">*</span></label>
                                <select id="location_id" name="location_id" class="ds-select @error('location_id') !border-red-400 @enderror" required>
                                    <option value="">اختر الموقع</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id') <p class="ds-error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="kind" class="ds-label">نوع العملية <span class="text-red-500">*</span></label>
                                <select id="kind" name="kind" x-model="kind" class="ds-select @error('kind') !border-red-400 @enderror" required>
                                    @if($canWaste)
                                        <option value="waste">هالك / تلف</option>
                                    @endif
                                    @if($canDonate)
                                        <option value="donate">تبرعات</option>
                                    @endif
                                </select>
                                @error('kind') <p class="ds-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <label for="boxes" class="ds-label !mb-0">أرقام العلب <span class="text-red-500">*</span></label>
                                <span class="text-[11px] font-semibold text-slate-400" x-text="boxCount ? boxCount + ' علبة' : ''"></span>
                            </div>
                            <textarea id="boxes" name="boxes" x-model="boxes" rows="5" dir="ltr"
                                class="ds-textarea font-mono text-sm @error('boxes') !border-red-400 @enderror"
                                placeholder="مثال: 12001, 12002, 12003&#10;أو ضع كل رقم في سطر" required>{{ old('boxes') }}</textarea>
                            @error('boxes') <p class="ds-error">{{ $message }}</p> @enderror
                            <p class="ds-help">افصل بين أرقام العلب بفاصلة أو مسافة أو سطر جديد. يقبل الفواصل العربية (،).</p>
                        </div>

                        <div>
                            <label for="reason" class="ds-label">السبب <span class="text-red-500">*</span></label>
                            <input id="reason" name="reason" value="{{ old('reason') }}" type="text" maxlength="255"
                                class="ds-input @error('reason') !border-red-400 @enderror"
                                placeholder="مثال: تلف أثناء النقل، انتهاء صلاحية، تبرع لجهة..." required>
                            @error('reason') <p class="ds-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-400">
                                <x-icon name="info" size="14" class="inline-block align-[-2px] ml-1" />
                                العملية تسجل حركات مخزون ولا يمكن التراجع عنها من هذه الشاشة.
                            </p>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('stock.index') }}" class="ds-btn ds-btn-secondary">إلغاء</a>
                                <button type="submit" class="ds-btn ds-btn-danger"
                                    x-bind:disabled="boxCount === 0">
                                    <x-icon name="trash-2" size="16" />
                                    <span x-text="kind === 'donate' ? 'تسجيل كتبرعات' : 'تسجيل كهالك'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                @endunless
            </div>
        </div>

        <div class="space-y-4">
            <div class="ds-card p-5">
                <h3 class="text-sm font-bold text-primary-800 mb-3 flex items-center gap-2">
                    <x-icon name="info" size="16" class="text-accent-500" />
                    إرشادات
                </h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-accent-500 shrink-0"></span><span><b class="text-primary-800">الهالك</b>: يخرج الصنف من المخزون ويُسجل كخسارة.</span></li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-accent-500 shrink-0"></span><span><b class="text-primary-800">التبرعات</b>: يخرج الصنف من الموقع ويُسجل كموقع تبرعات.</span></li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-accent-500 shrink-0"></span><span>يجب أن تكون العلب بحالة <b class="text-primary-800">مخزون</b> وفي نفس الموقع المختار.</span></li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-accent-500 shrink-0"></span><span>اكتب سببًا واضحًا لتسهيل المراجعة والتدقيق.</span></li>
                </ul>
            </div>

            <div class="ds-card p-5" x-show="boxCount > 0" x-cloak>
                <h3 class="text-sm font-bold text-primary-800 mb-3 flex items-center gap-2">
                    <x-icon name="scan-line" size="16" class="text-slate-400" />
                    ملخص العملية
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3 py-1.5 border-b border-slate-100">
                        <dt class="text-slate-500">عدد العلب</dt>
                        <dd class="font-bold text-primary-800 num" x-text="boxCount"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-1.5 border-b border-slate-100">
                        <dt class="text-slate-500">النوع</dt>
                        <dd class="font-bold" :class="kind === 'donate' ? 'text-accent-700' : 'text-red-600'" x-text="kind === 'donate' ? 'تبرعات' : 'هالك / تلف'"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-1.5">
                        <dt class="text-slate-500">الحالة</dt>
                        <dd class="ds-badge ds-badge-warning">جاهز للتنفيذ</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
