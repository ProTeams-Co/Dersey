<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

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
    <body class="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex justify-center">
                <img src="{{ asset('assets/logos/logo-dark.svg') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
            </div>

            <div class="rounded-xl border border-line bg-canvas p-8 shadow-sm">
                @yield('content')
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
