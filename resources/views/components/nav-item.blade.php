@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

<a href="{{ $href }}" {{ $attributes->class([
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm',
    'focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none',
    'text-muted hover:text-ink hover:bg-hover-surface' => ! $active,
    'bg-primary-tint text-primary font-medium' => $active,
]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="w-5 h-5" />
    @endif
    {{ $slot }}
</a>
