@php
    $categories = __('layout.categories');
@endphp

{{--
    sticky top-0 handles "stays pinned while scrolling" unconditionally, via
    CSS alone — that part never depends on JS or motion.js. The soft shadow
    that appears once the page is scrolled down is the only piece wired
    through modules/header.js + motion.js's ScrollTrigger, and that piece is
    simply absent (not replicated some other way) under reduced motion — see
    modules/header.js.
--}}
<header id="site-header" data-module="header" class="sticky top-0 z-sticky bg-canvas transition-shadow duration-200 ease-smooth motion-reduce:transition-none">
    {{-- ============================================================
         Top bar — desktop only. Breakpoint is lg (1024), not md (768):
         see the comment on the main row below for why.
    ============================================================ --}}
    <div class="hidden border-b border-line bg-surface lg:block">
        <div class="container flex h-9 items-center justify-between text-xs text-muted">
            <p>{{ __('layout.top_bar.free_shipping') }}</p>
            <a href="#" class="transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                {{ __('layout.top_bar.contact') }}
            </a>
        </div>
    </div>

    {{-- ============================================================
         Main row — desktop only. Split at lg (1024), not the more usual md
         (768): five spelled-out category labels plus the icon cluster
         don't fit next to the logo in a 768px tablet viewport (confirmed by
         building and measuring actual overflow at that width, not assumed)
         — tablet gets the same condensed mobile row as phones instead.
    ============================================================ --}}
    <div class="container hidden h-20 items-center justify-between gap-8 lg:flex">
        <a href="{{ route('home') }}" class="shrink-0">
            <img
                src="{{ asset('assets/logos/logo-green.svg') }}"
                alt="{{ __('layout.footer.logo_alt') }}"
                width="1078"
                height="689"
                class="h-9 w-auto"
            >
        </a>

        <nav aria-label="{{ __('layout.nav.menu') }}" class="relative flex h-full items-center gap-1">
            @foreach ($categories as $slug => $category)
                <div class="relative h-full">
                    <button
                        type="button"
                        data-mega-trigger="{{ $slug }}"
                        aria-expanded="false"
                        aria-controls="mega-panel-{{ $slug }}"
                        class="flex h-full items-center gap-1 px-4 text-sm font-medium text-ink transition-colors duration-150 ease-smooth hover:text-primary motion-reduce:transition-none"
                    >
                        {{ $category['name'] }}
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </nav>

        <div class="flex shrink-0 items-center gap-1">
            <button
                type="button"
                data-action="search-open"
                aria-label="{{ __('layout.nav.search') }}"
                class="rounded-full p-2.5 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                    <path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />
                </svg>
            </button>

            <a
                href="#"
                aria-label="{{ __('layout.nav.account') }}"
                class="rounded-full p-2.5 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />
                </svg>
            </a>

            <a
                href="#"
                aria-label="{{ __('layout.nav.wishlist') }}"
                class="rounded-full p-2.5 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                    <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
                </svg>
            </a>

            <button
                type="button"
                data-action="drawer-open"
                data-drawer-target="#cart-drawer"
                aria-label="{{ __('layout.nav.cart') }}"
                class="relative rounded-full p-2.5 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                    <path d="M16 10a4 4 0 0 1-8 0" /><path d="M3.103 6.034h17.794" />
                    <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
                </svg>
                <span
                    data-cart-count
                    aria-label="{{ __('layout.nav.cart_count') }}"
                    class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[0.625rem] font-semibold text-primary-foreground"
                >3</span>
            </button>
        </div>
    </div>

    <x-layout.mega-menu :categories="$categories" />

    {{-- ============================================================
         Mobile row — phones and tablets alike, up to lg (1024)
    ============================================================ --}}
    <div class="container flex h-16 items-center justify-between lg:hidden">
        <button
            type="button"
            data-action="drawer-open"
            data-drawer-target="#mobile-nav-drawer"
            aria-label="{{ __('layout.nav.open_menu') }}"
            class="-ms-2.5 rounded-full p-2.5 text-ink"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                <path d="M4 5h16" /><path d="M4 12h16" /><path d="M4 19h16" />
            </svg>
        </button>

        <a href="{{ route('home') }}">
            <img
                src="{{ asset('assets/logos/logo-green.svg') }}"
                alt="{{ __('layout.footer.logo_alt') }}"
                width="1078"
                height="689"
                class="h-7 w-auto"
            >
        </a>

        <button
            type="button"
            data-action="drawer-open"
            data-drawer-target="#cart-drawer"
            aria-label="{{ __('layout.nav.cart') }}"
            class="relative -me-2.5 rounded-full p-2.5 text-ink"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                <path d="M16 10a4 4 0 0 1-8 0" /><path d="M3.103 6.034h17.794" />
                <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
            </svg>
            <span
                data-cart-count
                aria-label="{{ __('layout.nav.cart_count') }}"
                class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[0.625rem] font-semibold text-primary-foreground"
            >3</span>
        </button>
    </div>

    {{-- Mobile search bar — always visible under the main row, not an icon
         trigger, since there's no room for a top-bar search affordance once
         the logo/burger/cart already fill the row. --}}
    <div class="container pb-3 lg:hidden">
        <button
            type="button"
            data-action="search-open"
            class="flex h-11 w-full items-center gap-2 rounded-full border border-border bg-surface px-4 text-sm text-muted"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
                <path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />
            </svg>
            {{ __('layout.search.placeholder') }}
        </button>
    </div>
</header>
