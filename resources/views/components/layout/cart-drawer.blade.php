@php
    /**
     * Placeholder rows only — this batch has no cart model, session cart, or
     * database query behind it (see Batch 1.5 scope). Prices are still run
     * through money() rather than formatted by hand, per the project's money
     * rules, even though the piaster amounts below are made up.
     */
    $items = [
        ['name' => __('layout.categories.dresses.name').' — '.__('layout.categories.dresses.subcategories.0'), 'size' => 'M', 'color' => __('layout.categories.dresses.name'), 'quantity' => 1, 'price' => 129900],
        ['name' => __('layout.categories.jackets.name').' — '.__('layout.categories.jackets.subcategories.0'), 'size' => 'L', 'color' => __('layout.categories.jackets.name'), 'quantity' => 2, 'price' => 89900],
    ];
    $subtotal = collect($items)->sum(fn ($item) => $item['price'] * $item['quantity']);
@endphp

<div
    id="cart-drawer-backdrop"
    data-drawer-backdrop="cart-drawer"
    hidden
    class="fixed inset-0 z-drawer bg-neutral-950/50 transition-opacity duration-200 ease-smooth starting:opacity-0 motion-reduce:transition-none"
></div>

{{--
    Slides from the logical end edge — left in Arabic, right in English —
    the mirror image of mobile-nav's start-edge drawer. See that component
    for why the entrance is animated (@starting-style) but the close isn't.
--}}
<div
    id="cart-drawer"
    data-module="cart-drawer"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="cart-drawer-heading"
    class="fixed inset-y-0 end-0 z-drawer flex w-full max-w-sm flex-col bg-canvas shadow-xl transition-transform duration-350 ease-smooth translate-x-0 ltr:starting:translate-x-full rtl:starting:-translate-x-full motion-reduce:transition-none"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
        <h2 id="cart-drawer-heading" class="text-lg font-semibold text-ink">{{ __('layout.cart.heading') }}</h2>
        <button
            type="button"
            data-action="drawer-close"
            data-drawer-target="#cart-drawer"
            aria-label="{{ __('common.close') }}"
            class="-me-2 rounded-full p-2 text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
            </svg>
        </button>
    </div>

    {{-- Skeleton — ready for the Ajax cart in a later batch (toggled via
         Dersey.loader.showSkeleton/hideSkeleton), not shown by default now. --}}
    <div data-cart-skeleton hidden class="flex-1 space-y-4 overflow-y-auto p-4">
        @for ($i = 0; $i < 3; $i++)
            <div class="flex gap-3 motion-reduce:animate-none animate-pulse">
                <div class="h-20 w-16 shrink-0 rounded-lg bg-surface"></div>
                <div class="flex-1 space-y-2 py-1">
                    <div class="h-3 w-3/4 rounded bg-surface"></div>
                    <div class="h-3 w-1/2 rounded bg-surface"></div>
                    <div class="h-3 w-1/4 rounded bg-surface"></div>
                </div>
            </div>
        @endfor
    </div>

    <div data-cart-content class="flex flex-1 flex-col overflow-hidden">
        @if (count($items))
            <ul class="flex-1 space-y-4 overflow-y-auto p-4">
                @foreach ($items as $item)
                    <li class="flex gap-3">
                        <div class="flex h-20 w-16 shrink-0 items-center justify-center rounded-lg bg-surface text-muted" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                <path d="M16 10a4 4 0 0 1-8 0" /><path d="M3.103 6.034h17.794" />
                                <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
                            </svg>
                        </div>

                        <div class="flex-1">
                            <p class="text-sm font-medium text-ink">{{ $item['name'] }}</p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ __('layout.cart.item_color') }}: {{ $item['color'] }} · {{ __('layout.cart.item_size') }}: {{ $item['size'] }}
                            </p>

                            <div class="mt-2 flex items-center justify-between">
                                <label class="text-xs text-muted">
                                    {{ __('layout.cart.quantity') }}
                                    <select class="ms-1 rounded-md border border-border-interactive bg-canvas py-1 text-xs text-ink" disabled>
                                        <option>{{ $item['quantity'] }}</option>
                                    </select>
                                </label>
                                <p class="text-sm font-semibold text-ink">{{ money($item['price'] * $item['quantity']) }}</p>
                            </div>
                        </div>

                        <button type="button" aria-label="{{ __('layout.cart.remove') }}" class="self-start text-muted transition-colors duration-150 ease-smooth hover:text-danger motion-reduce:transition-none">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="shrink-0 border-t border-line p-4">
                <div class="flex items-center justify-between text-sm font-medium text-ink">
                    <span>{{ __('layout.cart.subtotal') }}</span>
                    <span>{{ money($subtotal) }}</span>
                </div>
                <a href="#" class="mt-4 flex h-12 w-full items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground transition-shadow duration-150 ease-smooth hover:shadow-md motion-reduce:transition-none">
                    {{ __('layout.cart.checkout') }}
                </a>
                <button
                    type="button"
                    data-action="drawer-close"
                    data-drawer-target="#cart-drawer"
                    class="mt-2 flex h-11 w-full items-center justify-center rounded-full border border-border-interactive text-sm font-medium text-ink"
                >
                    {{ __('layout.cart.continue_shopping') }}
                </button>
            </div>
        @else
            <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-muted" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8">
                        <path d="M16 10a4 4 0 0 1-8 0" /><path d="M3.103 6.034h17.794" />
                        <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
                    </svg>
                </div>
                <p class="font-medium text-ink">{{ __('layout.cart.empty_heading') }}</p>
                <p class="text-sm text-muted">{{ __('layout.cart.empty_description') }}</p>
                <button
                    type="button"
                    data-action="drawer-close"
                    data-drawer-target="#cart-drawer"
                    class="mt-2 inline-flex h-11 items-center justify-center rounded-full bg-primary px-6 text-sm font-medium text-primary-foreground"
                >
                    {{ __('layout.cart.empty_cta') }}
                </button>
            </div>
        @endif
    </div>
</div>
