@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-primary text-surface font-semibold hover:bg-primary-hover',
        'secondary' => 'bg-surface text-ink border border-border hover:bg-hover-surface',
        'ghost' => 'text-muted hover:text-ink hover:bg-hover-surface',
    ];

    $sizes = [
        'md' => 'px-4 py-2 text-sm',
        'sm' => 'px-3 py-1.5 text-xs',
    ];

    $classes = collect([
        'inline-flex items-center gap-2 rounded-lg',
        'focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ])->implode(' ');
@endphp

<button type="{{ $type }}" {{ $attributes->class($classes) }}>
    {{ $slot }}
</button>
