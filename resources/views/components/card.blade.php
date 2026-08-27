@props(['title' => null])

<div {{ $attributes->class([
    'bg-surface border border-border rounded-2xl p-6',
    'shadow-[0_1px_2px_0_rgba(16,24,40,0.04),0_1px_3px_0_rgba(16,24,40,0.06)]',
]) }}>
    @if ($title)
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
