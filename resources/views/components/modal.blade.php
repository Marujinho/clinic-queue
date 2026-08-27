@props([
    'show' => false,
    'title' => null,
    'maxWidth' => 'lg',
])

{{--
    Presentational modal shell (overlay + panel).

    Visibility is controlled by the PARENT — wrap this component in an @if so the
    parent decides when it renders, e.g.:

        @if ($showModal)
            <x-modal title="Add Patient"
                     wire:click.self="$set('showModal', false)"      {{-- backdrop click closes --}}
                     x-on:close="$set('showModal', false)">
                ...form fields...
                <x-slot:footer>
                    <x-button variant="secondary" wire:click="$set('showModal', false)">Cancel</x-button>
                    <x-button wire:click="save">Save</x-button>
                </x-slot:footer>
            </x-modal>
        @endif

    Attributes merged onto the backdrop control closing on backdrop click
    (use wire:click.self or x-on:click.self). The header close button dispatches a
    browser `close` event; parents may listen with x-on:close or wire equivalents.
--}}

@php
    $maxWidths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
    $panelWidth = $maxWidths[$maxWidth] ?? $maxWidths['lg'];
@endphp

<div {{ $attributes->class(['fixed inset-0 z-40 flex items-center justify-center p-4 bg-ink/40']) }}>
    <div class="bg-surface rounded-2xl shadow-xl w-full {{ $panelWidth }} p-6" role="dialog" aria-modal="true">
        @if ($title || isset($close))
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                {{-- If a `close` slot is provided, the parent controls the close control
                     (e.g. <x-slot:close wire:click="$set('showModal', false)">); otherwise
                     the default button dispatches a browser `close` event. --}}
                @isset($close)
                    {{ $close }}
                @else
                    <button type="button"
                            aria-label="Close"
                            x-on:click="$dispatch('close')"
                            class="text-muted hover:text-ink hover:bg-hover-surface rounded-lg p-1 focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                @endisset
            </div>
        @endif

        <div class="text-sm text-ink">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 flex items-center justify-end gap-2">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
