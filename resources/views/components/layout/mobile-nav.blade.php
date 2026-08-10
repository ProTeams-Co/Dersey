@php
    $categories = __('layout.categories');
@endphp

{{--
    Slides in from the logical start edge — right in Arabic, left in
    English — via `start-0` positioning plus a starting-style transform that
    is itself direction-aware (rtl:/ltr: variants), never a physical
    translate-x that would slide from the same physical side in both
    directions. The @starting-style entrance is the same technique already
    proven in components/developer-modal.blade.php (starting:opacity-0
    starting:scale-95) — only the closing transition is skipped, matching
    that same precedent: core/modal.js's close() also hides instantly with
    no exit animation, so this stays consistent rather than inventing a new
    animated-close pattern used nowhere else in the codebase.

    Backdrop is a separate sibling element, not a pseudo-element, so drawer
    and backdrop can each have their own aria/hidden state.
--}}
<div
    id="mobile-nav-backdrop"
    data-drawer-backdrop="mobile-nav-drawer"
    hidden
    class="fixed inset-0 z-drawer bg-neutral-950/50 transition-opacity duration-200 ease-smooth starting:opacity-0 motion-reduce:transition-none"
></div>

<div
    id="mobile-nav-drawer"
    data-module="mobile-nav"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="mobile-nav-heading"
    class="fixed inset-y-0 start-0 z-drawer flex w-full max-w-xs flex-col bg-canvas shadow-xl transition-transform duration-350 ease-smooth translate-x-0 ltr:starting:-translate-x-full rtl:starting:translate-x-full motion-reduce:transition-none"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
        <h2 id="mobile-nav-heading" class="text-lg font-semibold text-ink">{{ __('layout.mobile_nav.heading') }}</h2>
        <button
            type="button"
            data-action="drawer-close"
            data-drawer-target="#mobile-nav-drawer"
            aria-label="{{ __('layout.nav.close_menu') }}"
            class="-me-2 rounded-full p-2 text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-2">
        <ul>
            @foreach ($categories as $slug => $category)
                <li class="border-b border-line last:border-b-0">
                    <button
                        type="button"
                        data-accordion-trigger="{{ $slug }}"
                        aria-expanded="false"
                        aria-controls="mobile-accordion-{{ $slug }}"
                        class="flex w-full items-center justify-between px-2 py-3.5 text-start text-sm font-medium text-ink"
                    >
                        {{ $category['name'] }}
                        <svg data-accordion-chevron xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 transition-transform duration-150 ease-smooth motion-reduce:transition-none" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                    <ul id="mobile-accordion-{{ $slug }}" data-accordion-panel="{{ $slug }}" hidden class="pb-3 ps-4">
                        @foreach ($category['subcategories'] as $subcategory)
                            <li>
                                <a href="#" class="block px-2 py-2 text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                                    {{ $subcategory }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>

        <ul class="mt-2 border-t border-line pt-2">
            <li>
                <a href="#" class="block px-2 py-3 text-sm font-medium text-ink">{{ __('layout.nav.account') }}</a>
            </li>
            <li>
                <a href="#" class="block px-2 py-3 text-sm font-medium text-ink">{{ __('layout.nav.wishlist') }}</a>
            </li>
        </ul>
    </nav>

    <div class="shrink-0 border-t border-line px-4 py-4">
        <x-locale-switcher />
    </div>
</div>
