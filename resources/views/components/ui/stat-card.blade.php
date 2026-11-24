@props(['label','value','unit'=>'','meta'=>null,'href'=>null,'icon'=>null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class'=>'ds-kpi block group']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="ds-kpi-label">{{ $label }}</div>
            <div class="ds-kpi-value mt-1">
                <span class="num">{{ $value }}</span>
                @if($unit)<span class="text-xs font-semibold text-slate-400 mr-1">{{ $unit }}</span>@endif
            </div>
            @if($meta)<div class="ds-kpi-meta">{{ $meta }}</div>@endif
        </div>
        @if($icon)
            <span class="ds-kpi-icon shrink-0"><x-icon :name="$icon" size="20" /></span>
        @endif
    </div>
</{{ $tag }}>
