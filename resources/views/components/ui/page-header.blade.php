@props(['title', 'subtitle' => null, 'actions' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-extrabold text-primary-800 leading-tight">{{ $title }}</h1>
        @if($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @if($actions)
        <div class="flex flex-wrap items-center gap-2 shrink-0">{!! $actions !!}</div>
    @endif
    {{ $slot }}
</div>
