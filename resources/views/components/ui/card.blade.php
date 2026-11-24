@props(['padding' => true, 'title' => null, 'subtitle' => null])
<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-2xl shadow-[0_1px_2px_rgba(23,50,77,.04)]']) }}>
    @if($title)
        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-primary-800">{{ $title }}</h3>
                @if($subtitle)<p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
