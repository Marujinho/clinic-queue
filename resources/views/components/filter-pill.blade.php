@props([
    'label' => null,
    'icon' => null,
])

<button type="button" {{ $attributes->class([
    'inline-flex items-center gap-2 text-xs font-medium text-ink bg-surface border border-border rounded-lg px-3 py-1.5 hover:bg-hover-surface',
    'focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none',
]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="w-4 h-4" />
    @endif
    @if ($label)
        <span>{{ $label }}</span>
    @endif
    {{ $slot }}
    <x-icon name="chevron-down" class="w-4 h-4" />
</button>
