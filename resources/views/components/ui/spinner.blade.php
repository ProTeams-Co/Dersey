@props([
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-4 w-4',
        'md' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
    ];
@endphp

<svg
    {{ $attributes->merge(['class' => 'animate-spin motion-reduce:animate-none ' . ($sizes[$size] ?? $sizes['md'])]) }}
    viewBox="0 0 24 24"
    fill="none"
    role="status"
    aria-label="{{ __('common.loading') }}"
>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
</svg>
