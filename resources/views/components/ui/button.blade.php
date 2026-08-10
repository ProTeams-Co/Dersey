@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'loading' => false,
    'fullWidth' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:bg-primary-700 active:bg-primary-900',
        'secondary' => 'bg-neutral-200 text-ink hover:bg-neutral-300 active:bg-neutral-400',
        'outline' => 'border border-interactive bg-transparent text-ink hover:bg-surface active:bg-neutral-200',
        'ghost' => 'bg-transparent text-ink hover:bg-surface active:bg-neutral-200',
        'danger' => 'bg-danger text-danger-foreground hover:bg-danger-700 active:bg-danger-900',
    ];

    $sizes = [
        'sm' => 'h-9 gap-1.5 px-3.5 text-sm',
        'md' => 'h-11 gap-2 px-5 text-sm',
        'lg' => 'h-13 gap-2 px-6 text-base',
    ];

    $classes = 'inline-flex shrink-0 items-center justify-center rounded-lg font-medium transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md'])
        . ($fullWidth ? ' w-full' : '');

    $isDisabled = $attributes->get('disabled') || $loading;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($isDisabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->except('disabled')->merge(['class' => $classes . ($isDisabled ? ' pointer-events-none opacity-50' : '')]) }}
    >
        @if ($loading)
            <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
            </svg>
        @elseif (isset($iconStart))
            <span aria-hidden="true">{{ $iconStart }}</span>
        @endif

        {{ $slot }}

        @if (! $loading && isset($iconEnd))
            <span aria-hidden="true">{{ $iconEnd }}</span>
        @endif
    </a>
@else
    <button
        type="{{ $attributes->get('type', 'button') }}"
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->except('type')->merge(['class' => $classes]) }}
        @if ($isDisabled) disabled @endif
    >
        @if ($loading)
            <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
            </svg>
        @elseif (isset($iconStart))
            <span aria-hidden="true">{{ $iconStart }}</span>
        @endif

        {{ $slot }}

        @if (! $loading && isset($iconEnd))
            <span aria-hidden="true">{{ $iconEnd }}</span>
        @endif
    </button>
@endif
