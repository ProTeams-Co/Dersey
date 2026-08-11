@props(['title', 'breadcrumbs' => []])

<div class="border-b border-line bg-canvas px-4 py-4 lg:px-6">
    @if ($breadcrumbs !== [])
        <x-ui.breadcrumb :items="$breadcrumbs" class="mb-2" />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-ink">{{ $title }}</h1>

        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>

<div class="p-4 lg:p-6">
    {{ $slot }}
</div>
