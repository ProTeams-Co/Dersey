{{--
    A thin delegate to the existing x-ui.card (Batch 1.6) - kept here as
    its own admin.* component only so admin views have one consistent
    namespace to import from, not because the admin panel needs different
    visuals from the storefront's own card.
--}}
@props([])

<x-ui.card {{ $attributes }}>
    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ui.card>
