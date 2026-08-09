{{--
    Plain <a> links only — no JavaScript, so a crawler (or a user with JS
    disabled) can still follow them to the other locale. Styling is
    deliberately minimal here; the real design lands in Batch 1.5.
--}}
<nav aria-label="{{ __('common.language_switcher') }}" class="flex gap-3 text-sm">
    @foreach (LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        <a
            href="{{ LaravelLocalization::getLocalizedURL($localeCode) }}"
            rel="alternate"
            hreflang="{{ $localeCode }}"
            @if ($localeCode === app()->getLocale()) aria-current="page" @endif
        >
            {{ $properties['native'] }}
        </a>
    @endforeach
</nav>
