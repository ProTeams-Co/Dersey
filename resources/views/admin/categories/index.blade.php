@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.categories.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.categories.title')],
        ]"
    >
        <x-slot:actions>
            @if (auth('admin')->user()?->can('categories.create'))
                <x-ui.button :href="route('admin.categories.create')" size="sm">
                    {{ __('admin.table.create') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <form method="GET" class="mb-4 max-w-sm">
            <label for="category-tree-search" class="sr-only">{{ __('admin.table.search_placeholder') }}</label>
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-muted" />
                <input
                    id="category-tree-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('admin.table.search_placeholder') }}"
                    class="w-full rounded-lg border border-interactive bg-canvas py-2 ps-9 pe-3 text-sm text-ink placeholder:text-muted transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none"
                >
            </div>
        </form>

        @if ($mode === 'search')
            <p class="mb-3 text-sm text-muted">
                {{ __('admin.categories.search_results_for', ['q' => request('q')]) }}
                &middot;
                <a href="{{ route('admin.categories.index') }}" class="text-primary hover:underline">{{ __('admin.categories.back_to_tree') }}</a>
            </p>

            <x-admin.table :table="$table" />
        @else
            @if ($roots->isEmpty())
                <x-admin.empty :title="__('admin.categories.no_categories')" />
            @else
                <div data-category-tree data-reorder-url-template="{{ route('admin.categories.reorder', ['id' => '__ID__']) }}">
                    <ul data-sortable-level data-parent-id="" class="space-y-1">
                        @foreach ($roots as $root)
                            @include('admin.categories._tree-node', ['category' => $root, 'depth' => 0, 'productCounts' => $productCounts, 'directProductCategoryIds' => $directProductCategoryIds])
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </x-admin.page>
@endsection
