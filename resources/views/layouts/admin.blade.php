<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            {{-- json from a plain variable, never inline — see layouts/app.blade.php for why. --}}
            @php
                $derseyData = [
                    'locale' => 'ar',
                    'dir' => 'rtl',
                    'routes' => [
                        'csrfToken' => route('ajax.csrf-token'),
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
    <body>
        @yield('content')

        @stack('scripts')
    </body>
</html>
