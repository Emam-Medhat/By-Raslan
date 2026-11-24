@if (session('status'))
    <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 no-print" x-data="{show:true}" x-show="show" x-transition>
        <span class="mt-0.5 text-emerald-600 shrink-0"><x-icon name="circle-check" size="18" /></span>
        <div class="flex-1 font-medium">{{ session('status') }}</div>
        <button type="button" @click="show=false" class="shrink-0 rounded-md p-0.5 text-emerald-500 hover:bg-emerald-100 hover:text-emerald-700" aria-label="إغلاق">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if (session('error'))
    <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 no-print" x-data="{show:true}" x-show="show" x-transition>
        <span class="mt-0.5 text-red-500 shrink-0"><x-icon name="circle-alert" size="18" /></span>
        <div class="flex-1 font-medium">{{ session('error') }}</div>
        <button type="button" @click="show=false" class="shrink-0 rounded-md p-0.5 text-red-400 hover:bg-red-100 hover:text-red-600" aria-label="إغلاق">
            <x-icon name="x" size="16" />
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 no-print">
        <div class="flex items-center gap-2 font-bold mb-1.5">
            <x-icon name="circle-alert" size="16" class="text-red-500" />
            يرجى مراجعة البيانات التالية
        </div>
        <ul class="list-disc space-y-0.5 pr-5 text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
