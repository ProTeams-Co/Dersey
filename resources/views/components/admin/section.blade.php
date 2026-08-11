@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'space-y-4 border-b border-line pb-6 last:border-b-0 last:pb-0']) }}>
    @if ($title || $description)
        <div>
            @if ($title)
                <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
            @endif

            @if ($description)
                <p class="mt-0.5 text-sm text-muted">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="space-y-4">
        {{ $slot }}
    </div>
</section>
