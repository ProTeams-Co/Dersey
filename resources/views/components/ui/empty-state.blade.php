@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 px-6 py-12 text-center']) }}>
    @isset($icon)
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-muted" aria-hidden="true">
            {{ $icon }}
        </div>
    @endisset

    @if ($title)
        <p class="text-lg font-medium text-ink">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">
            {{ $action }}
        </div>
    @endisset
</div>
