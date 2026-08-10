@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'neutral' => 'bg-neutral-200 text-ink',
        'success' => 'bg-success text-success-foreground',
        'warning' => 'bg-warning text-warning-foreground',
        'danger' => 'bg-danger text-danger-foreground',
        'accent' => 'bg-accent text-accent-foreground',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full font-medium '
        . ($variants[$variant] ?? $variants['neutral']) . ' '
        . ($sizes[$size] ?? $sizes['md']),
]) }}>
    {{ $slot }}
</span>
