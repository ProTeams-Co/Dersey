{{--
    Full-screen, so it IS its own backdrop — no separate backdrop element
    needed like the drawers. Entrance fades in via @starting-style, same
    proven pattern as developer-modal.blade.php; closes instantly, no exit
    animation, for the same reason documented in mobile-nav.blade.php.

    [data-search-input] and [data-search-results] are the two hooks a later
    batch's Ajax wiring needs: modules/search.js already calls
    Dersey.ajax.request() with an explicit `key` on every keystroke so a
    fast typist's requests dedupe (.abort() the previous one) instead of
    racing — see core/ajax.js's inFlight map — but there is no live backend
    endpoint to call yet, so it currently just guards the empty/filled state
    of the placeholder panel below.
--}}
<div
    id="search-overlay"
    data-module="search"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="search-overlay-heading"
    class="fixed inset-0 z-drawer flex flex-col bg-canvas transition-opacity duration-200 ease-smooth starting:opacity-0 motion-reduce:transition-none"
>
    <div class="border-b border-line">
        <div class="container flex h-16 items-center gap-3 md:h-20">
            <h2 id="search-overlay-heading" class="sr-only">{{ __('layout.search.heading') }}</h2>

            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-muted" aria-hidden="true">
                <path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />
            </svg>

            <input
                type="search"
                data-search-input
                placeholder="{{ __('layout.search.placeholder') }}"
                class="h-full flex-1 border-0 bg-transparent text-base text-ink placeholder:text-muted focus:outline-none md:text-lg"
            >

            <button
                type="button"
                data-action="drawer-close"
                data-drawer-target="#search-overlay"
                aria-label="{{ __('common.close') }}"
                class="-me-2 shrink-0 rounded-full p-2 text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="container flex-1 overflow-y-auto py-8">
        <div data-search-empty-state>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.search.popular_heading') }}</h3>
            <ul class="mt-4 flex flex-wrap gap-2">
                @foreach (__('layout.search.popular_terms') as $term)
                    <li>
                        <button type="button" data-search-term class="rounded-full border border-border-interactive px-4 py-2 text-sm text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none">
                            {{ $term }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div data-search-results hidden>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.search.results_heading') }}</h3>
            <p class="mt-4 text-sm text-muted">{{ __('layout.search.results_placeholder') }}</p>
        </div>
    </div>
</div>
