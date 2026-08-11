{{--
    The one thing every admin listing page renders. Works two ways off the
    exact same AdminTable instance:
      - First load (no JS, or JS hasn't run yet): this Blade markup below
        IS the real, already-paginated/searched/filtered/sorted HTML - a
        plain GET reload from here (clicking a sort header, submitting the
        search box, changing a page number) still works with zero JS.
      - After JS runs: admin/table.js intercepts those same links/forms
        and calls AdminTable::response() (JSON) instead, re-rendering just
        [data-table-body] - see that file for the swap logic.
--}}
@props(['table'])

@php
    $paginator = $table->paginator();
    $columns = $table->columns();
    $filters = $table->filters();
    $bulkActions = $table->visibleBulkActions();
    $rowActionsExist = $table->rowActions() !== [];
    $sort = $table->currentSort();
    $search = $table->currentSearch();
    $applied = $table->currentFilters();

    $sortUrl = fn (string $key, string $direction) => url()->current().'?'.http_build_query([
        ...request()->except(['sort', 'direction', 'page']),
        'sort' => $key,
        'direction' => $direction,
    ]);
@endphp

<div data-admin-table data-table-url="{{ url()->current() }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" data-admin-search class="relative">
            @foreach (request()->except(['q', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $subKey => $subValue)
                        <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label for="admin-table-search" class="sr-only">{{ __('admin.table.search_placeholder') }}</label>
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-muted" />
                <input
                    id="admin-table-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('admin.table.search_placeholder') }}"
                    class="w-64 rounded-lg border border-interactive bg-canvas py-2 ps-9 pe-3 text-sm text-ink placeholder:text-muted transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none"
                >
            </div>
        </form>

        <div class="flex items-center gap-2">
            <div data-bulk-actions-bar hidden class="flex items-center gap-2">
                <span data-selected-count class="text-sm text-muted"></span>

                @foreach ($bulkActions as $action)
                    <button
                        type="button"
                        data-bulk-action="{{ $action['key'] }}"
                        @if (! empty($action['confirm'])) data-confirm data-confirm-message="{{ __('admin.table.confirm_bulk_action') }}" @endif
                        class="rounded-lg border border-interactive px-3 py-1.5 text-sm font-medium text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none {{ ($action['variant'] ?? null) === 'danger' ? 'border-danger/30 text-danger hover:bg-danger/10' : '' }}"
                    >
                        {{ __($action['label']) }}
                    </button>
                @endforeach
            </div>

            <a
                href="{{ url()->current() }}?{{ http_build_query([...request()->except('page'), 'export' => 1]) }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-interactive px-3 py-1.5 text-sm font-medium text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none"
            >
                <x-ui.icon name="download" class="h-4 w-4" />
                {{ __('admin.table.export') }}
            </a>
        </div>
    </div>

    @if ($filters !== [])
        <x-admin.filters :filters="$filters" :applied="$applied" class="mb-4" />
    @endif

    @if ($paginator->isEmpty())
        <x-admin.empty />
    @else
        <div class="overflow-x-auto rounded-xl border border-line">
            <table class="w-full text-start text-sm">
                <thead class="border-b border-line bg-surface">
                    <tr>
                        @if ($bulkActions !== [])
                            <th class="w-10 ps-4">
                                <input type="checkbox" data-select-all aria-label="{{ __('admin.table.select_all') }}">
                            </th>
                        @endif

                        @foreach ($columns as $column)
                            <th class="px-4 py-3 text-{{ $column['align'] ?? 'start' }} font-medium text-muted">
                                @if ($column['sortable'] ?? false)
                                    <a
                                        href="{{ $sortUrl($column['key'], $sort['key'] === $column['key'] && $sort['direction'] === 'asc' ? 'desc' : 'asc') }}"
                                        data-sort-link
                                        data-sort-key="{{ $column['key'] }}"
                                        data-sort-direction="{{ $sort['key'] === $column['key'] && $sort['direction'] === 'asc' ? 'desc' : 'asc' }}"
                                        class="inline-flex items-center gap-1 transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
                                    >
                                        {{ __($column['label']) }}

                                        @if ($sort['key'] === $column['key'])
                                            <x-ui.icon name="chevron-down" class="h-3.5 w-3.5 {{ $sort['direction'] === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </a>
                                @else
                                    {{ __($column['label']) }}
                                @endif
                            </th>
                        @endforeach

                        @if ($rowActionsExist)
                            <th class="px-4 py-3 text-end font-medium text-muted">{{ __('admin.table.actions') }}</th>
                        @endif
                    </tr>
                </thead>

                <tbody data-table-body class="divide-y divide-line">
                    @foreach ($paginator as $row)
                        @php $formatted = $table->formatRow($row); @endphp
                        <tr data-row-id="{{ $row->getKey() }}">
                            @if ($bulkActions !== [])
                                <td class="ps-4 py-3">
                                    <input type="checkbox" data-row-select value="{{ $row->getKey() }}" aria-label="{{ __('admin.table.select_all') }}">
                                </td>
                            @endif

                            @foreach ($columns as $column)
                                <td class="px-4 py-3 text-{{ $column['align'] ?? 'start' }} text-ink">
                                    @if (isset($column['format']))
                                        {!! $formatted[$column['key']] !!}
                                    @else
                                        {{ $formatted[$column['key']] }}
                                    @endif
                                </td>
                            @endforeach

                            @if ($rowActionsExist)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @foreach ($formatted['_actions'] as $action)
                                            <a
                                                href="{{ $action['url'] }}"
                                                @if (($action['method'] ?? 'get') !== 'get') data-row-action-method="{{ $action['method'] }}" @endif
                                                @if (! empty($action['confirm'])) data-confirm data-confirm-message="{{ __('admin.table.confirm_bulk_action') }}" @endif
                                                class="rounded-lg p-1.5 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none"
                                                title="{{ __($action['label']) }}"
                                            >
                                                @if (! empty($action['icon']))
                                                    <x-ui.icon :name="$action['icon']" class="h-4 w-4" />
                                                @else
                                                    <span class="text-xs">{{ __($action['label']) }}</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted">
                {{ __('admin.table.showing', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
            </p>

            <x-ui.pagination
                :current-page="$paginator->currentPage()"
                :total-pages="$paginator->lastPage()"
                :base-url="url()->current().'?'.http_build_query(request()->except('page'))"
            />
        </div>
    @endif
</div>
