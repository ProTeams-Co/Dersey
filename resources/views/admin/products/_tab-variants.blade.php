{{--
    Batch 3.2-B. Not an <x-admin.form> submit like the other tabs - this is
    an Ajax-driven, JS-rendered/edited data grid (App\Support\Admin\AdminTable
    is the paginated-list engine, explicitly out of scope to touch here;
    this table is neither paginated nor list-shaped). The server renders
    the CURRENT state once (works with zero JS, same progressive-
    enhancement principle as everywhere else in the admin), and
    resources/js/admin/product-variants.js (lazy-loaded the first time this
    tab opens - see product-form.js) takes over from there: preview,
    generate, inline edit, bulk price edit, per-row optimistic-lock version
    tracking, save.
--}}
@php
    $toDecimal = fn (?App\Support\Money $money) => $money === null ? null : number_format($money->minor() / 100, 2, '.', '');
    $currentAttributeIds = $variants->isNotEmpty()
        ? $variants->first()->attributeValues->pluck('attribute_id')->unique()->values()->all()
        : [];
    $currentValueIdsByAttribute = $variants->isNotEmpty()
        ? $variants->flatMap->attributeValues->pluck('id')->unique()->values()->all()
        : [];
@endphp

<div
    data-variant-matrix
    data-preview-url="{{ route('admin.products.variants.preview', $model->id) }}"
    data-generate-url="{{ route('admin.products.variants.generate', $model->id) }}"
    data-update-url="{{ route('admin.products.variants.update', $model->id) }}"
    data-toggle-url-template="{{ route('admin.products.variants.toggle', [$model->id, '__ID__']) }}"
    data-max-combinations="{{ $variantMatrixMaxCombinations }}"
    data-current-attribute-ids="{{ implode(',', $currentAttributeIds) }}"
>
    <x-admin.section :title="__('admin.products.variant_axes_title')">
        @if ($variantAttributes->isEmpty())
            <p class="text-sm text-muted">{{ __('admin.products.variant_no_attributes') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($variantAttributes as $attribute)
                    <div data-variant-attribute-group data-attribute-id="{{ $attribute->id }}">
                        <p class="mb-1.5 text-sm font-medium text-ink">{{ $attribute->translate('ar')?->name }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($attribute->values as $value)
                                <label class="flex items-center gap-1.5 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        data-variant-value-checkbox
                                        data-attribute-id="{{ $attribute->id }}"
                                        value="{{ $value->id }}"
                                        @checked(in_array($value->id, $currentValueIdsByAttribute, true))
                                    >
                                    {{ $value->translate('ar')?->value }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div data-variant-default-values class="mt-4 space-y-2" hidden></div>

            <div class="mt-4 flex items-center gap-3">
                <p data-variant-preview-text class="text-sm text-muted"></p>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <x-ui.button type="button" data-variant-generate-button>{{ __('admin.products.variant_generate_button') }}</x-ui.button>
            </div>
        @endif
    </x-admin.section>

    <x-admin.section :title="__('admin.products.variant_matrix_title')" class="mt-6">
        @if ($variants->isEmpty())
            <p class="text-sm text-muted" data-variant-empty-message>{{ __('admin.products.variant_matrix_empty') }}</p>
        @endif

        <div class="mb-3 flex flex-wrap items-center gap-3" @if ($variants->isEmpty()) hidden @endif data-variant-toolbar>
            <input
                type="search"
                data-variant-filter
                placeholder="{{ __('admin.products.variant_filter_placeholder') }}"
                class="w-56 rounded-lg border border-interactive bg-canvas px-3 py-1.5 text-sm text-ink placeholder:text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >

            <span class="text-sm text-muted" data-variant-counts></span>

            <div class="flex items-center gap-1.5">
                <input
                    type="number" step="0.01" min="0"
                    data-variant-bulk-price
                    placeholder="{{ __('admin.products.variant_bulk_price_placeholder') }}"
                    class="w-32 rounded-lg border border-interactive bg-canvas px-3 py-1.5 text-sm text-ink placeholder:text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                <x-ui.button type="button" variant="outline" size="sm" data-variant-bulk-apply>{{ __('admin.products.variant_bulk_apply') }}</x-ui.button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-line" @if ($variants->isEmpty()) hidden @endif data-variant-table-wrap>
            <table class="w-full text-start text-sm">
                <thead class="border-b border-line bg-surface">
                    <tr>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_options') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_sku') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_price') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_compare_price') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_stock') }}</th>
                        <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.products.variant_column_active') }}</th>
                    </tr>
                </thead>
                <tbody data-variant-table-body>
                    @foreach ($variants as $variant)
                        <tr
                            data-variant-row
                            data-variant-id="{{ $variant->id }}"
                            data-variant-version="{{ $variant->version }}"
                            data-variant-active="{{ $variant->is_active ? '1' : '0' }}"
                        >
                            <td class="px-3 py-2 text-ink" dir="ltr">{{ $variant->optionsLabel('ar') }}</td>
                            <td class="px-3 py-2">
                                <input
                                    type="text"
                                    dir="ltr"
                                    data-variant-sku
                                    value="{{ $variant->sku }}"
                                    class="w-36 rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                >
                            </td>
                            <td class="px-3 py-2">
                                <input
                                    type="number" step="0.01" min="0"
                                    data-variant-price
                                    value="{{ $toDecimal($variant->price) }}"
                                    placeholder="{{ $toDecimal($model->base_price) }}"
                                    class="w-24 rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                >
                            </td>
                            <td class="px-3 py-2">
                                <input
                                    type="number" step="0.01" min="0"
                                    data-variant-compare-price
                                    value="{{ $toDecimal($variant->compare_at_price) }}"
                                    class="w-24 rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                >
                            </td>
                            <td class="px-3 py-2">
                                @if ($variant->movements_count === 0)
                                    <input
                                        type="number" min="0" step="1"
                                        data-variant-initial-stock
                                        value="0"
                                        placeholder="{{ __('admin.products.variant_initial_stock_placeholder') }}"
                                        class="w-24 rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    >
                                @else
                                    <span class="text-muted" title="{{ __('admin.products.variant_stock_readonly_hint') }}">{{ $variant->stock_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <input type="checkbox" data-variant-active-toggle @checked($variant->is_active)>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4" @if ($variants->isEmpty()) hidden @endif data-variant-save-wrap>
            <x-ui.button type="button" data-variant-save-button>{{ __('admin.products.variant_save_button') }}</x-ui.button>
        </div>
    </x-admin.section>
</div>
