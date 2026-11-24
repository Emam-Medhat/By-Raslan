@props(['variant' => 'neutral'])
@php
    $map = [
        'neutral' => 'ds-badge ds-badge-neutral',
        'primary' => 'ds-badge ds-badge-primary',
        'accent' => 'ds-badge ds-badge-accent',
        'success' => 'ds-badge ds-badge-success',
        'warning' => 'ds-badge ds-badge-warning',
        'danger' => 'ds-badge ds-badge-danger',
        'info' => 'ds-badge ds-badge-info',
    ];
@endphp
<span {{ $attributes->merge(['class' => $map[$variant] ?? $map['neutral']]) }}>{{ $slot }}</span>
