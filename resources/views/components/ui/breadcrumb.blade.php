@props([
    'items' => [],
])

{{--
    No direction handling needed for the trail order itself — a plain flex
    row already follows the inline reading direction on its own (DOM order
    stays Home -> Category -> Product; it just mirrors under dir="rtl" the
    same way regular text does). Only the separator glyph needs an explicit
    flip, since a chevron pointing structurally "forward" points the wrong
    physical way once the reading direction itself is mirrored.
--}}
<nav aria-label="{{ __('components.breadcrumb.nav_label') }}" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-muted">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if ($index > 0)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0 rtl:rotate-180" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                @endif

                @if (! empty($item['href']) && $index !== count($items) - 1)
                    <a href="{{ $item['href'] }}" class="transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ $item['label'] }}</a>
                @else
                    <span class="text-ink" @if ($index === count($items) - 1) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
