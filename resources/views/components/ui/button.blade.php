@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
])
@php
    $variants = [
        'primary' => 'ds-btn ds-btn-primary',
        'accent' => 'ds-btn ds-btn-accent',
        'secondary' => 'ds-btn ds-btn-secondary',
        'ghost' => 'ds-btn ds-btn-ghost',
        'danger' => 'ds-btn ds-btn-danger',
        'success' => 'ds-btn ds-btn-success',
    ];
    $sizes = ['sm' => 'ds-btn-sm', 'md' => '', 'lg' => 'ds-btn-lg'];
    $cls = trim(($variants[$variant] ?? $variants['secondary']) . ' ' . ($sizes[$size] ?? ''));
    $iconSize = $size === 'sm' ? 14 : ($size === 'lg' ? 18 : 16);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
    </button>
@endif
