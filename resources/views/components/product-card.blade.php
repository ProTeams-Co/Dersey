@props([
    'name' => null,
    'price' => null,
    'originalPrice' => null,
    'image' => null,
    'imageAlt' => '',
    'discountPercent' => null,
    'colors' => [],
    'href' => '#',
])

@php
    /**
     * No real product-color palette exists yet (no Color model this batch) —
     * swatches are placeholders drawn from the existing semantic tokens, not
     * arbitrary per-product hex. A caller passes a token key from this fixed
     * set; anything else falls back to muted. Keeping this a closed PHP map
     * (rather than interpolating an arbitrary token into a bg-{{ $token }}
     * class string) is deliberate — Tailwind's build-time scanner only picks
     * up class names that appear literally in source, and a dynamically
     * interpolated one would silently generate no CSS at all.
     */
    $swatches = [
        'ink' => 'bg-ink',
        'primary' => 'bg-primary',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'muted' => 'bg-muted',
        'canvas' => 'bg-canvas',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'group/card w-full']) }}>
    <a href="{{ $href }}" class="block focus-visible:outline-none">
        {{-- Fixed aspect-ratio, not a min-height guess — the box is reserved
             before the image ever loads, so CLS is zero regardless of when
             (or whether) it finishes loading. --}}
        <div class="relative aspect-3/4 w-full overflow-hidden rounded-xl bg-surface">
            @if ($image)
                <img
                    src="{{ $image }}"
                    alt="{{ $imageAlt }}"
                    loading="lazy"
                    class="h-full w-full object-cover transition-transform duration-350 ease-smooth group-hover/card:scale-105 motion-reduce:transition-none motion-reduce:group-hover/card:scale-100"
                >
            @endif

            @if ($discountPercent)
                <x-ui.badge variant="danger" class="absolute start-3 top-3">-{{ $discountPercent }}%</x-ui.badge>
            @endif

            <button
                type="button"
                aria-label="{{ __('common.add_to_cart') }}"
                class="absolute bottom-3 end-3 flex h-10 w-10 items-center justify-center rounded-full bg-canvas text-ink opacity-0 shadow-md transition-opacity duration-150 ease-smooth group-hover/card:opacity-100 group-focus-within/card:opacity-100 motion-reduce:transition-none motion-reduce:opacity-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                    <path d="M16 10a4 4 0 0 1-8 0" /><path d="M3.103 6.034h17.794" />
                    <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
                </svg>
            </button>
        </div>

        <div class="mt-3">
            <p class="truncate text-sm text-ink">{{ $name }}</p>

            <div class="mt-1 flex items-center gap-2">
                @if ($price !== null)
                    <p class="text-sm font-semibold text-ink">{{ money($price) }}</p>
                @endif
                @if ($originalPrice)
                    <p class="text-xs text-muted line-through">{{ money($originalPrice) }}</p>
                @endif
            </div>

            @if (count($colors))
                <div class="mt-2 flex items-center gap-1.5">
                    @foreach ($colors as $color)
                        <span
                            class="h-3.5 w-3.5 rounded-full border border-line {{ $swatches[$color['token']] ?? $swatches['muted'] }}"
                            title="{{ $color['label'] ?? '' }}"
                        ></span>
                    @endforeach
                </div>
            @endif
        </div>
    </a>
</div>
