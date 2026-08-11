@php
    $admin = auth('admin')->user();
@endphp

<header class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-canvas px-4 lg:px-6">
    <button
        type="button"
        data-sidebar-open
        class="rounded-lg p-2 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none lg:hidden"
        aria-label="{{ __('admin.layout.open_menu') }}"
    >
        <x-ui.icon name="menu" class="h-5 w-5" />
    </button>

    <x-ui.breadcrumb :items="$breadcrumbs ?? [['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')]]" class="hidden md:block" />

    <div class="ms-auto flex items-center gap-2">
        <form role="search" class="hidden md:block" data-admin-quick-search>
            <label for="admin-quick-search" class="sr-only">{{ __('admin.layout.search_placeholder') }}</label>
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-muted" />
                <input
                    id="admin-quick-search"
                    type="search"
                    placeholder="{{ __('admin.layout.search_placeholder') }}"
                    class="w-56 rounded-lg border border-interactive bg-canvas py-2 ps-9 pe-3 text-sm text-ink placeholder:text-muted transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none"
                >
            </div>
        </form>

        <div class="relative" data-dropdown>
            <button
                type="button"
                data-dropdown-toggle
                class="relative rounded-lg p-2 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none"
                aria-label="{{ __('admin.layout.notifications') }}"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <x-ui.icon name="bell" class="h-5 w-5" />
            </button>

            <div data-dropdown-panel hidden class="absolute end-0 z-20 mt-2 w-72 rounded-lg border border-line bg-canvas p-2 shadow-lg">
                <p class="px-2 py-1.5 text-xs font-medium text-muted">{{ __('admin.layout.notifications') }}</p>
                <p class="px-2 py-4 text-center text-sm text-muted">{{ __('admin.layout.no_notifications') }}</p>
            </div>
        </div>

        <div class="relative" data-dropdown>
            <button
                type="button"
                data-dropdown-toggle
                class="flex items-center gap-2 rounded-lg p-1.5 transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
                    {{ mb_substr($admin?->name ?? '', 0, 1) }}
                </span>
                <span class="hidden text-start text-sm font-medium text-ink md:block">{{ $admin?->name }}</span>
                <x-ui.icon name="chevron-down" class="hidden h-4 w-4 text-muted md:block" />
            </button>

            <div data-dropdown-panel hidden class="absolute end-0 z-20 mt-2 w-56 rounded-lg border border-line bg-canvas p-2 shadow-lg">
                <div class="px-2 py-1.5">
                    <p class="truncate text-sm font-medium text-ink">{{ $admin?->name }}</p>
                    <p class="truncate text-xs text-muted" dir="ltr">{{ $admin?->email }}</p>
                </div>

                <hr class="my-1.5 border-line">

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none">
                        <x-ui.icon name="log-out" class="h-4 w-4 text-muted" />
                        {{ __('admin.auth.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
