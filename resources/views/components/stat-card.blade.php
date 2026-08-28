@props([
    'label',
    'value',
    'delta' => null,
    'deltaType' => 'success',
    'icon' => null,
    'hero' => false,
])

@php
    $base = 'rounded-2xl p-6';
    if ($hero) {
        $container = "$base bg-primary text-surface shadow-[0_8px_24px_-8px_rgba(52,170,134,0.45)]";
        $labelClass = 'text-xs font-medium text-surface/80';
        $valueClass = 'text-3xl font-bold text-surface';
        $deltaClass = 'text-xs font-medium text-surface/80';
    } else {
        $container = "$base bg-surface border border-border shadow-[0_1px_2px_0_rgba(16,24,40,0.04),0_1px_3px_0_rgba(16,24,40,0.06)]";
        $labelClass = 'text-xs font-medium text-muted';
        $valueClass = 'text-3xl font-bold text-ink';
        $deltaClass = 'text-xs font-medium ' . ($deltaType === 'danger' ? 'text-danger' : 'text-success');
    }
@endphp

<div {{ $attributes->class($container) }}>
    <div class="flex items-start justify-between">
        <span class="{{ $labelClass }}">{{ $label }}</span>
        @if ($icon)
            <span class="inline-flex items-center justify-center rounded-lg w-8 h-8 {{ $hero ? 'bg-surface/20 text-surface' : 'bg-primary-tint text-primary' }}">
                <x-icon :name="$icon" class="w-5 h-5" />
            </span>
        @endif
    </div>

    <div class="mt-3 {{ $valueClass }}">{{ $value }}</div>

    @if ($delta)
        <div class="mt-1 {{ $deltaClass }}">{{ $delta }}</div>
    @endif
</div>
