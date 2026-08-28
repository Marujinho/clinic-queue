@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'md' => 'w-9 h-9 text-sm',
        'sm' => 'w-8 h-8 text-xs',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['rounded-full object-cover', $sizeClass]) }} />
@else
    <span {{ $attributes->class([
        'rounded-full inline-flex items-center justify-center font-medium bg-primary-tint text-primary',
        $sizeClass,
    ]) }} aria-label="{{ $name }}">
        {{ $initials }}
    </span>
@endif
