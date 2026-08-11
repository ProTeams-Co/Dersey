@props(['label', 'value', 'icon' => null, 'trend' => null, 'trendUp' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-canvas p-5']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm text-muted">{{ $label }}</p>

        @if ($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <x-ui.icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    <p class="mt-2 text-2xl font-semibold text-ink">{{ $value }}</p>

    @if ($trend)
        <p class="mt-1 text-xs {{ $trendUp ? 'text-success' : 'text-danger' }}">{{ $trend }}</p>
    @endif
</div>
