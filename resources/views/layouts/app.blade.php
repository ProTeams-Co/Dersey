@inject('seo', \App\Services\Seo\SeoService::class)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        {!! $seo->tags() !!}

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            {{--
                Preload is locale-conditional and never both — an /en page has
                no business fetching Arabic glyphs before the browser even
                knows it needs them, and vice versa. Only 400 (body) + the
                page's heavier weight are worth the round trip this early;
                everything else can wait for font-display: swap.
            --}}
            @if (app()->getLocale() === 'ar')
                <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-400.woff2') }}">
                <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-600.woff2') }}">
            @else
                <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/satoshi/satoshi-400.woff2') }}">
                <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/satoshi/satoshi-700.woff2') }}">
            @endif

            {{--
                The json Blade helper, never string concatenation, is the
                only XSS-safe way to embed server data inside an inline
                script tag. Injected before the vite include on purpose:
                app.js reads window.Dersey at load time, so it must already
                exist.

                The array is built as a plain variable first, not inline —
                Blade's directive-argument parser does not reliably balance
                nested brackets and parens in a complex inline expression,
                and silently truncates at the first closing paren it meets
                (the route helper's, here) instead of the real end. A plain
                variable has no nesting for it to get wrong. Do not inline
                this back — see this same note in layouts/admin.blade.php.
            --}}
            @php
                $derseyData = [
                    'locale' => app()->getLocale(),
                    'dir' => LaravelLocalization::getCurrentLocaleDirection(),
                    'routes' => [
                        'csrfToken' => route('ajax.csrf-token'),
                    ],
                    'lang' => trans('js'),
                ];
            @endphp
            <script>
                window.Dersey = @json($derseyData);
            </script>

            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @stack('head')
    </head>
    <body>
        <x-layout.header />

        @yield('content')

        <x-layout.footer />

        {{--
            Mounted once, globally, here rather than per-page: drawers/overlay
            are fixed-position and triggered from the header on every page, so
            there is exactly one instance of each in the DOM regardless of
            which view is rendering into @yield('content') above.
        --}}
        <x-layout.mobile-nav />
        <x-layout.cart-drawer />
        <x-layout.search-overlay />

        @stack('scripts')
    </body>
</html>
