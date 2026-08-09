@inject('seo', \App\Services\Seo\SeoService::class)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

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

            @vite(['resources/css/app.css'])
        @endif

        @stack('head')
    </head>
    <body>
        @yield('content')

        @stack('scripts')
    </body>
</html>
