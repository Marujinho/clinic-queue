@props([
    'variant' => 'neutral',
    'label',
])

@php
    $variants = [
        'success' => 'bg-success-tint text-success',
        'warning' => 'bg-warning-tint text-warning',
        'danger' => 'bg-danger-tint text-danger',
        'info' => 'bg-info-tint text-info',
        'neutral' => 'bg-hover-surface text-muted',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    {{ $label }}
</span>
