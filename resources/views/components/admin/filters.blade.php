{{--
    A plain GET form - filter[key]=value query params, same ones
    AdminTable::currentFilters() reads. Works with zero JS (submitting
    reloads the page, already filtered); admin/table.js optionally
    hijacks the submit for an Ajax re-fetch instead.
--}}
@props(['filters' => [], 'applied' => []])

<form method="GET" data-admin-filters {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-3']) }}>
    @foreach (request()->except(['filter', 'page']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $subValue)
                <input type="hidden" name="{{ $key }}[]" value="{{ $subValue }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    @foreach ($filters as $filter)
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-medium text-muted">{{ __($filter['label']) }}</label>

            @if ($filter['type'] === 'date_range')
                <div class="flex items-center gap-1.5">
                    <input
                        type="date"
                        name="filter[{{ $filter['key'] }}][from]"
                        value="{{ $applied[$filter['key']]['from'] ?? '' }}"
                        class="rounded-lg border border-interactive bg-canvas px-2.5 py-2 text-sm text-ink"
                    >
                    <span class="text-muted" aria-hidden="true">&ndash;</span>
                    <input
                        type="date"
                        name="filter[{{ $filter['key'] }}][to]"
                        value="{{ $applied[$filter['key']]['to'] ?? '' }}"
                        class="rounded-lg border border-interactive bg-canvas px-2.5 py-2 text-sm text-ink"
                    >
                </div>
            @else
                @php
                    $options = match (true) {
                        $filter['type'] === 'boolean' => ['1' => __('common.confirm'), '0' => __('common.cancel')],
                        is_callable($filter['options'] ?? null) => $filter['options'](),
                        default => $filter['options'] ?? [],
                    };
                @endphp

                <select name="filter[{{ $filter['key'] }}]" class="w-full rounded-lg border border-interactive bg-canvas px-3 py-2 text-sm text-ink">
                    <option value="">{{ __('admin.table.filters') }}</option>
                    @foreach ($options as $value => $optionLabel)
                        <option value="{{ $value }}" @selected(($applied[$filter['key']] ?? null) == $value)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @endforeach

    <x-ui.button type="submit" variant="outline" size="sm">{{ __('admin.table.apply_filters') }}</x-ui.button>

    @if ($applied !== [])
        <a href="{{ url()->current() }}" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink hover:underline motion-reduce:transition-none">
            {{ __('admin.table.clear_filters') }}
        </a>
    @endif
</form>
