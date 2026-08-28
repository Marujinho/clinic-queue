@props(['head' => null])

{{--
    Data table wrapper (§7.6).

    Usage — pass the header cells via the `head` slot and the body rows via the default slot:

    <x-data-table>
        <x-slot:head>
            <th class="text-left px-3 pb-3">Patient</th>
            <th class="text-left px-3 pb-3">Status</th>
        </x-slot:head>

        <tr class="border-b border-border hover:bg-hover-surface">
            <td class="py-3 px-3 text-ink">Jane Doe</td>
            <td class="py-3 px-3 text-ink"><x-status-badge variant="success" label="Confirmed" /></td>
        </tr>
    </x-data-table>

    Row markup: `<tr class="border-b border-border hover:bg-hover-surface">`
    Cell markup: `<td class="py-3 text-ink">` (add horizontal padding as needed).
--}}

<div {{ $attributes->class(['overflow-x-auto']) }}>
    <table class="w-full text-sm">
        @isset($head)
            <thead>
                <tr class="[&>th]:text-xs [&>th]:font-medium [&>th]:text-muted border-b border-border">
                    {{ $head }}
                </tr>
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
