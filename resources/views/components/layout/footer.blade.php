@php
    $categories = __('layout.categories');
    $serviceLinks = __('layout.footer.customer_service_links');
    /**
     * Lucide (the package this project depends on for icons) dropped brand
     * logos years ago — there is no Instagram/Facebook/X icon to pull from
     * it. These three are hand-inlined instead, the same approach already
     * used for the GitHub mark in components/developer-modal.blade.php.
     */
@endphp

<footer class="border-t border-line bg-surface">
    {{-- ============================================================
         Newsletter
    ============================================================ --}}
    <div class="border-b border-line">
        <div class="container flex flex-col items-center gap-4 py-10 text-center md:flex-row md:justify-between md:text-start">
            <div>
                <h2 class="text-lg font-semibold text-ink">{{ __('layout.footer.newsletter_heading') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('layout.footer.newsletter_description') }}</p>
            </div>

            <form class="flex w-full max-w-sm gap-2">
                <label for="footer-newsletter-email" class="sr-only">{{ __('layout.footer.newsletter_placeholder') }}</label>
                <input
                    id="footer-newsletter-email"
                    type="email"
                    placeholder="{{ __('layout.footer.newsletter_placeholder') }}"
                    class="h-11 flex-1 rounded-full border border-border-interactive bg-canvas px-4 text-sm text-ink placeholder:text-muted"
                >
                <button type="submit" class="h-11 shrink-0 rounded-full bg-primary px-5 text-sm font-medium text-primary-foreground">
                    {{ __('layout.footer.newsletter_cta') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         4 columns
    ============================================================ --}}
    <div class="container grid grid-cols-2 gap-8 py-12 md:grid-cols-4">
        <div>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.footer.about_heading') }}</h3>
            <p class="mt-4 text-sm text-muted">{{ __('layout.footer.about_text') }}</p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.footer.customer_service_heading') }}</h3>
            <ul class="mt-4 space-y-3">
                @foreach ($serviceLinks as $link)
                    <li><a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ $link }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.footer.categories_heading') }}</h3>
            <ul class="mt-4 space-y-3">
                @foreach ($categories as $category)
                    <li><a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ $category['name'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink">{{ __('layout.footer.contact_heading') }}</h3>
            <ul class="mt-4 space-y-3 text-sm text-muted">
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
                        <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" /><rect x="2" y="4" width="20" height="16" rx="2" />
                    </svg>
                    {{ __('layout.footer.contact_email') }}
                </li>
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
                        <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
                    </svg>
                    {{ __('layout.footer.contact_phone') }}
                </li>
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /><circle cx="12" cy="10" r="3" />
                    </svg>
                    {{ __('layout.footer.contact_address') }}
                </li>
            </ul>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-ink">{{ __('layout.footer.social_heading') }}</h3>
                <div class="mt-3 flex items-center gap-3">
                    <a href="#" aria-label="Instagram" class="text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" /><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" /><line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                        </svg>
                    </a>
                    <a href="#" aria-label="Facebook" class="text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path d="M22 12a10 10 0 1 0-11.5 9.87v-6.98h-2.5v-2.89h2.5V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.89h-2.34v6.98A10 10 0 0 0 22 12Z" />
                        </svg>
                    </a>
                    <a href="#" aria-label="X" class="text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Payment methods — plain text badges, not brand-accurate logos;
         no payment-network asset exists in the project yet.
    ============================================================ --}}
    <div class="border-t border-line">
        <div class="container flex flex-col items-center gap-3 py-6 md:flex-row md:justify-between">
            <p class="text-xs font-medium text-muted">{{ __('layout.footer.payment_heading') }}</p>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['Visa', 'Mastercard', 'meeza', 'Fawry'] as $method)
                    <span class="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted">{{ $method }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============================================================
         Bottom bar
    ============================================================ --}}
    <div class="border-t border-line">
        <div class="container flex flex-col items-center justify-between gap-4 py-6 md:flex-row">
            <img
                src="{{ asset('assets/logos/logo-proteamsco-black.png') }}"
                alt="{{ __('layout.footer.ptc_logo_alt') }}"
                width="102"
                height="32"
                class="h-6 w-auto"
            >

            <p class="text-xs text-muted">{{ __('layout.footer.copyright', ['year' => date('Y')]) }}</p>

            <x-locale-switcher />
        </div>
    </div>
</footer>
