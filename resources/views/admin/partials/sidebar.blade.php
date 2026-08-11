@php
    $menu = \App\Support\Admin\Menu::visible();
@endphp

<aside
    id="admin-sidebar"
    data-sidebar
    class="fixed inset-y-0 start-0 z-30 flex w-68 flex-col border-e border-line bg-canvas transition-[width] duration-200 ease-smooth motion-reduce:transition-none in-[.sidebar-collapsed]:w-19 max-lg:-translate-x-full max-lg:rtl:translate-x-full max-lg:in-[.sidebar-open]:translate-x-0"
>
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-line px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 overflow-hidden">
            <img src="{{ asset('assets/logos/logo-green.svg') }}" alt="{{ config('app.name') }}" class="h-7 w-7 shrink-0">
            <span class="truncate text-sm font-semibold text-ink in-[.sidebar-collapsed]:hidden">{{ config('app.name') }}</span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4" aria-label="{{ __('admin.menu.dashboard') }}">
        @foreach ($menu as $item)
            @if (isset($item['children']))
                <div data-sidebar-group>
                    <button
                        type="button"
                        data-sidebar-group-toggle
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ $item['active'] ? 'bg-surface' : '' }}"
                        title="{{ __($item['label']) }}"
                    >
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate text-start in-[.sidebar-collapsed]:hidden">{{ __($item['label']) }}</span>
                        <x-ui.icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform duration-150 ease-smooth in-[.sidebar-collapsed]:hidden in-data-sidebar-group-open:rotate-180" />
                    </button>

                    <ul
                        data-sidebar-group-panel
                        class="{{ $item['active'] ? '' : 'hidden' }} ms-4 mt-1 space-y-1 border-s border-line ps-4 in-[.sidebar-collapsed]:hidden"
                        @if ($item['active']) data-sidebar-group-open @endif
                    >
                        @foreach ($item['children'] as $child)
                            <li>
                                @if ($child['exists'])
                                    <a
                                        href="{{ $child['url'] }}"
                                        class="block rounded-lg px-3 py-2 text-sm text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ $child['active'] ? 'bg-primary/10 font-medium text-primary' : 'text-muted' }}"
                                    >
                                        {{ __($child['label']) }}
                                    </a>
                                @else
                                    <span class="flex cursor-not-allowed items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-muted opacity-50">
                                        {{ __($child['label']) }}
                                        <span class="text-xs">({{ __('admin.menu.coming_soon') }})</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                @if ($item['exists'])
                    <a
                        href="{{ $item['url'] }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ $item['active'] ? 'bg-primary/10 text-primary' : 'text-ink' }}"
                        title="{{ __($item['label']) }}"
                        @if ($item['active']) aria-current="page" @endif
                    >
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="truncate in-[.sidebar-collapsed]:hidden">{{ __($item['label']) }}</span>
                    </a>
                @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-muted opacity-50"
                        title="{{ __($item['label']) }}"
                    >
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="truncate in-[.sidebar-collapsed]:hidden">{{ __($item['label']) }}</span>
                    </span>
                @endif
            @endif
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-line p-3">
        <button
            type="button"
            data-sidebar-collapse-toggle
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none"
            aria-label="{{ __('admin.layout.collapse_sidebar') }}"
        >
            <x-ui.icon name="panel" class="h-5 w-5 shrink-0" />
            <span class="in-[.sidebar-collapsed]:hidden">{{ __('admin.layout.collapse_sidebar') }}</span>
        </button>
    </div>
</aside>

<div
    data-sidebar-backdrop
    class="fixed inset-0 z-20 hidden bg-ink/40 lg:hidden in-[.sidebar-open]:block"
></div>
