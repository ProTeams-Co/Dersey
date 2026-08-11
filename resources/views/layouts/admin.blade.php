<!DOCTYPE html>
<html lang="ar" dir="rtl" class="{{ request()->cookie('admin_sidebar_collapsed') === '1' ? 'sidebar-collapsed' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @php
                $derseyData = [
                    'locale' => 'ar',
                    'dir' => 'rtl',
                    'routes' => [
                        'csrfToken' => route('ajax.csrf-token'),
                        'login' => route('admin.login'),
                    ],
                    'lang' => trans('js'),
                ];
            @endphp
            <script>
                window.Dersey = @json($derseyData);
            </script>

            @vite(['resources/css/admin.css', 'resources/js/admin.js'])
        @endif

        @stack('head')
    </head>
    <body class="bg-surface text-ink">
        <a href="#admin-content" class="sr-only focus-visible:not-sr-only focus-visible:fixed focus-visible:inset-s-4 focus-visible:top-4 focus-visible:z-50 focus-visible:rounded-lg focus-visible:bg-canvas focus-visible:px-4 focus-visible:py-2 focus-visible:shadow-lg">
            {{ __('admin.layout.skip_to_content') }}
        </a>

        @include('admin.partials.sidebar')

        <div class="transition-[padding] duration-200 ease-smooth motion-reduce:transition-none lg:ps-68 in-[.sidebar-collapsed]:lg:ps-19">
            @include('admin.partials.topbar')

            <main id="admin-content" class="min-h-[calc(100vh-4rem)]">
                @yield('content')
            </main>
        </div>

        <x-admin.confirm />

        @stack('scripts')
    </body>
</html>
