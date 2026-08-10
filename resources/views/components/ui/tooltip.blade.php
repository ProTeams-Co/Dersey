@props([
    'text' => null,
    'position' => 'top',
    'id' => null,
])

@php
    $id = $id ?? 'tooltip-'.\Illuminate\Support\Str::random(8);

    /**
     * Centering needs a direction-aware translate, not just a logical inset —
     * start-1/2 alone lands at the horizontal center in both directions, but
     * -translate-x-1/2 always shifts left (a physical transform), which is
     * the wrong half in rtl.
     */
    $positions = [
        'top' => 'bottom-full start-1/2 mb-2 ltr:-translate-x-1/2 rtl:translate-x-1/2',
        'bottom' => 'top-full start-1/2 mt-2 ltr:-translate-x-1/2 rtl:translate-x-1/2',
    ];
@endphp

{{-- Caller's trigger element (in the default slot) should carry
     aria-describedby="{{ $id }}" itself for full screen-reader wiring —
     this component can't safely inject an attribute into arbitrary slot
     content. Visibility via hover/keyboard focus works either way. --}}
<span class="group/tooltip relative inline-flex">
    {{ $slot }}

    <span
        id="{{ $id }}"
        role="tooltip"
        {{ $attributes->merge([
            'class' => 'pointer-events-none absolute z-tooltip whitespace-nowrap rounded-md bg-neutral-950 px-2.5 py-1.5 text-xs text-canvas opacity-0 transition-opacity duration-150 ease-smooth group-hover/tooltip:opacity-100 group-focus-within/tooltip:opacity-100 motion-reduce:transition-none '
                . ($positions[$position] ?? $positions['top']),
        ]) }}
    >
        {{ $text }}
    </span>
</span>
