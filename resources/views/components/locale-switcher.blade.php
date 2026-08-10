{{--
    Plain <a> links only — no JavaScript, so a crawler (or a user with JS
    disabled) can still follow them to the other locale.
--}}
<nav aria-label="{{ __('common.language_switcher') }}" class="flex items-center gap-1 rounded-full border border-border p-1 text-sm">
    @foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        <a
            href="{{ LaravelLocalization::getLocalizedURL($localeCode) }}"
            rel="alternate"
            hreflang="{{ $localeCode }}"
            @if ($localeCode === app()->getLocale()) aria-current="page" @endif
            @class([
                'rounded-full px-3 py-1 transition-colors duration-150 ease-smooth motion-reduce:transition-none',
                'bg-primary text-primary-foreground' => $localeCode === app()->getLocale(),
                'text-muted hover:text-ink' => $localeCode !== app()->getLocale(),
            ])
        >
            {{ $properties['native'] }}
        </a>
    @endforeach
</nav>
