@props([
    'currentPage' => 1,
    'totalPages' => 1,
    'baseUrl' => '#',
])

@php
    $currentPage = max(1, (int) $currentPage);
    $totalPages = max(1, (int) $totalPages);

    $pages = collect(range(1, $totalPages))
        ->filter(fn ($page) => $page === 1 || $page === $totalPages || abs($page - $currentPage) <= 1)
        ->values();

    $urlFor = fn ($page) => $baseUrl.(str_contains($baseUrl, '?') ? '&' : '?').'page='.$page;
@endphp

<nav aria-label="{{ __('components.pagination.nav_label') }}" {{ $attributes }}>
    <ul class="flex items-center gap-1 text-sm">
        <li>
            <a
                href="{{ $currentPage > 1 ? $urlFor($currentPage - 1) : '#' }}"
                @if ($currentPage <= 1) aria-disabled="true" tabindex="-1" @endif
                class="flex h-9 items-center justify-center rounded-lg px-3 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ $currentPage <= 1 ? 'pointer-events-none opacity-50' : '' }}"
            >
                {{ __('components.pagination.previous') }}
            </a>
        </li>

        @php $previousPage = null; @endphp
        @foreach ($pages as $page)
            @if ($previousPage !== null && $page - $previousPage > 1)
                <li class="px-1 text-muted" aria-hidden="true">&hellip;</li>
            @endif

            <li>
                @if ($page === $currentPage)
                    <span aria-current="page" class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-medium text-primary-foreground">{{ $page }}</span>
                @else
                    <a
                        href="{{ $urlFor($page) }}"
                        aria-label="{{ __('components.pagination.go_to_page', ['page' => $page]) }}"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-sm text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
                    >{{ $page }}</a>
                @endif
            </li>

            @php $previousPage = $page; @endphp
        @endforeach

        <li>
            <a
                href="{{ $currentPage < $totalPages ? $urlFor($currentPage + 1) : '#' }}"
                @if ($currentPage >= $totalPages) aria-disabled="true" tabindex="-1" @endif
                class="flex h-9 items-center justify-center rounded-lg px-3 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '' }}"
            >
                {{ __('components.pagination.next') }}
            </a>
        </li>
    </ul>
</nav>
