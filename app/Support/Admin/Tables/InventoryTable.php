<?php

namespace App\Support\Admin\Tables;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Support\Admin\AdminTable;
use App\Support\Search\ArabicNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Batch 3.3 - the unit of listing is the VARIANT (product_variants), not
 * the product, since stock itself lives on variants (CLAUDE.md).
 *
 * Two of AdminTable's built-in generic mechanisms don't fit here and are
 * deliberately bypassed, same "declare it, implement manually" pattern
 * ProductsTable already established for its own category/stock-status
 * filters:
 *   - Sorting/searching by the product's translated NAME: applyTranslatedSort()/
 *     the translated half of applySearch() both call $model->translationModel()
 *     on THIS table's own model (ProductVariant) - which has no
 *     translations of its own (the name lives on the PRODUCT, a different
 *     model). Handled by hand in query()/filteredQuery() instead.
 *   - Sorting by available_quantity: a computed accessor
 *     (stock_quantity - reserved_quantity), not a real column - can't be
 *     passed to a plain orderBy(). Handled via reorder() + orderByRaw()
 *     in filteredQuery(), same "override filteredQuery(), don't touch
 *     AdminTable" pattern ProductsTable uses for its own id-tiebreaker.
 */
class InventoryTable extends AdminTable
{
    public function columns(): array
    {
        return [
            [
                'key' => 'image',
                'label' => 'admin.inventory.column_image',
                'align' => 'center',
                'format' => fn (ProductVariant $variant) => $this->thumbnail($variant),
            ],
            [
                'key' => 'product_name',
                'label' => 'admin.inventory.column_product',
                'sortable' => true,
                'format' => fn (ProductVariant $variant) => $this->productCell($variant),
            ],
            [
                'key' => 'options',
                'label' => 'admin.inventory.column_options',
                'format' => fn (ProductVariant $variant) => e($variant->optionsLabel('ar')),
            ],
            ['key' => 'sku', 'label' => 'admin.inventory.column_sku'],
            ['key' => 'stock_quantity', 'label' => 'admin.inventory.column_stock', 'sortable' => true, 'align' => 'center'],
            [
                'key' => 'reserved_quantity',
                'label' => 'admin.inventory.column_reserved',
                'align' => 'center',
                'format' => fn (ProductVariant $variant) => (string) $variant->reserved_quantity,
            ],
            [
                'key' => 'available_quantity',
                'label' => 'admin.inventory.column_available',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (ProductVariant $variant) => (string) $variant->available_quantity,
            ],
            ['key' => 'low_stock_threshold', 'label' => 'admin.inventory.column_threshold', 'align' => 'center'],
            [
                'key' => 'status',
                'label' => 'admin.inventory.column_status',
                'align' => 'center',
                'format' => fn (ProductVariant $variant) => $this->statusBadge($variant),
            ],
            [
                'key' => 'last_movement_at',
                'label' => 'admin.inventory.column_last_movement',
                'sortable' => true,
                'format' => fn (ProductVariant $variant) => $this->lastMovementCell($variant),
            ],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'stock_status', 'type' => 'variant_stock_status', 'label' => 'admin.inventory.filter_stock_status', 'options' => fn () => $this->stockStatusOptions()],
            ['key' => 'category_id', 'type' => 'category_tree', 'label' => 'admin.inventory.filter_category', 'options' => fn () => $this->categoryOptions()],
            ['key' => 'brand_id', 'type' => 'variant_brand', 'label' => 'admin.inventory.filter_brand', 'options' => fn () => $this->brandOptions()],
            // A custom type (not the native 'boolean') - AdminTable's own
            // x-admin.filters Blade partial hardcodes 'boolean' to always
            // render generic "Confirm"/"Cancel" option labels, ignoring
            // whatever `options` a filter declares (same reason
            // ProductsTable's own trashed-only filter uses a custom
            // 'trashed_only' type instead of 'boolean').
            ['key' => 'inactive_only', 'type' => 'inactive_only', 'label' => 'admin.inventory.filter_inactive', 'options' => fn () => ['1' => __('admin.inventory.filter_inactive')]],
        ];
    }

    public function rowActions(): array
    {
        return [
            [
                'key' => 'adjust',
                'label' => 'admin.inventory.adjust_button',
                'icon' => 'pencil',
                'url' => fn (ProductVariant $variant) => route('admin.inventory.adjust.create', $variant->id),
                'permission' => 'inventory.update',
            ],
            [
                'key' => 'threshold',
                'label' => 'admin.inventory.threshold_button',
                'icon' => 'settings',
                'url' => fn (ProductVariant $variant) => route('admin.inventory.threshold.edit', $variant->id),
                'permission' => 'inventory.update',
            ],
            [
                'key' => 'movements',
                'label' => 'admin.inventory.view_movements_button',
                'icon' => 'menu',
                'url' => fn (ProductVariant $variant) => route('admin.inventory.movements.index', ['filter' => ['variant_id' => $variant->id]]),
                'permission' => 'inventory.view',
            ],
        ];
    }

    public function with(): array
    {
        $locales = array_unique([app()->getLocale(), config('app.fallback_locale')]);

        return [
            'product.translations' => fn ($query) => $query->whereIn('locale', $locales),
            'product.images',
            'image',
            'attributeValues.attribute',
            'attributeValues.translations' => fn ($query) => $query->whereIn('locale', $locales),
        ];
    }

    public function query(): Builder
    {
        $query = ProductVariant::query()->withMax('movements as last_movement_at', 'created_at');

        $this->applyProductSearch($query);
        $this->applyStockStatusFilter($query);
        $this->applyCategoryFilter($query);
        $this->applyBrandFilter($query);
        $this->applyInactiveOnlyFilter($query);

        return $query;
    }

    public function defaultSort(): array
    {
        return ['key' => 'stock_quantity', 'direction' => 'asc'];
    }

    /**
     * 50, not AdminTable's own default of 20 - an inventory list is
     * scanned/skimmed far more than most admin tables (checking many SKUs
     * at once for low stock), and this is also the page size this batch's
     * own N+1 requirement measures against.
     */
    public function perPage(): int
    {
        return 50;
    }

    /**
     * id tiebreaker (same reasoning/pattern as ProductsTable::filteredQuery())
     * + manual handling for the two sort keys the generic applySort() can't
     * resolve on its own (see class docblock) - both already got a normal
     * (wrong) orderBy() appended by parent::filteredQuery()'s own
     * applySort() call by this point, so reorder() clears that before
     * adding the real one.
     */
    public function filteredQuery(): Builder
    {
        $query = parent::filteredQuery();
        $sort = $this->currentSort();

        if ($sort['key'] === 'available_quantity') {
            $query->reorder()->orderByRaw(
                '(product_variants.stock_quantity - product_variants.reserved_quantity) '.$sort['direction']
            );
        } elseif ($sort['key'] === 'product_name') {
            $locale = app()->getLocale();
            $query->reorder()
                ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
                ->leftJoin('product_translations', function ($join) use ($locale) {
                    $join->on('product_translations.product_id', '=', 'products.id')
                        ->where('product_translations.locale', '=', $locale);
                })
                ->addSelect('product_variants.*')
                ->orderBy('product_translations.name', $sort['direction']);
        }

        $query->orderBy('product_variants.id', $sort['direction']);

        return $query;
    }

    /**
     * Search by SKU (a real product_variants column) OR the owning
     * product's translated name, normalized the same way ArabicNormalizer
     * is used everywhere else - implemented here instead of via
     * columns()' `searchable`/translatedSearchColumns() because those two
     * conditions need to be OR'd together in ONE where(), and AdminTable's
     * generic applySearch() (sku) + would-be translated half would
     * otherwise land in two separately AND'd where() calls instead.
     */
    private function applyProductSearch(Builder $query): void
    {
        $term = $this->currentSearch();

        if ($term === null || $term === '') {
            return;
        }

        $normalized = ArabicNormalizer::normalize($term);
        $locale = app()->getLocale();

        $query->where(function (Builder $q) use ($term, $normalized, $locale) {
            $q->where('product_variants.sku', 'like', "%{$term}%")
                ->orWhereHas('product.translations', function (Builder $tq) use ($normalized, $locale) {
                    $tq->where('locale', $locale)
                        ->whereRaw(ArabicNormalizer::sqlExpression('name').' LIKE ?', ['%'.$normalized.'%']);
                });
        });
    }

    /**
     * Per-VARIANT (not summed across a product's variants like
     * ProductsTable::applyStockStatusFilter() does) - a single row's own
     * stock_quantity/reserved_quantity/low_stock_threshold, no
     * aggregation needed at this grain.
     */
    private function applyStockStatusFilter(Builder $query): void
    {
        $status = $this->request->input('filter.stock_status');

        if (! in_array($status, ['in', 'low', 'out'], true)) {
            return;
        }

        match ($status) {
            'out' => $query->whereRaw('(stock_quantity - reserved_quantity) <= 0'),
            'low' => $query->whereRaw('(stock_quantity - reserved_quantity) > 0')
                ->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold'),
            'in' => $query->whereRaw('(stock_quantity - reserved_quantity) > low_stock_threshold'),
        };
    }

    /**
     * Category filter must include descendants (Task 3's own requirement) -
     * same descendants()->pluck('id')->push($category->id) technique
     * ProductsTable::applyCategoryFilter() uses, just reached through the
     * owning product relation since product_variants has no category
     * column of its own.
     */
    private function applyCategoryFilter(Builder $query): void
    {
        $categoryId = $this->request->input('filter.category_id');

        if (! $categoryId) {
            return;
        }

        $category = Category::find($categoryId);

        if (! $category) {
            return;
        }

        $ids = $category->descendants()->pluck('id')->push($category->id);

        $query->whereHas('product', fn (Builder $q) => $q->whereIn('primary_category_id', $ids));
    }

    private function applyBrandFilter(Builder $query): void
    {
        $brandId = $this->request->input('filter.brand_id');

        if (! $brandId) {
            return;
        }

        $query->whereHas('product', fn (Builder $q) => $q->where('brand_id', $brandId));
    }

    private function applyInactiveOnlyFilter(Builder $query): void
    {
        if ($this->request->input('filter.inactive_only') === '1') {
            $query->where('is_active', false);
        }
    }

    private function thumbnail(ProductVariant $variant): string
    {
        $image = $variant->image ?? $variant->product->images->firstWhere('is_primary', true);

        if (! $image) {
            return '<span class="flex h-10 w-10 items-center justify-center rounded bg-surface text-muted" style="width:2.5rem;height:2.5rem" width="40" height="40"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></span>';
        }

        $url = e(Storage::disk(config('filesystems.default'))->url($image->path));
        $alt = e($image->getTranslation('alt', 'ar') ?? '');

        return '<img src="'.$url.'" width="'.$image->width.'" height="'.$image->height
            .'" style="aspect-ratio:'.$image->width.'/'.$image->height.'" class="h-10 w-10 rounded object-cover" alt="'.$alt.'">';
    }

    private function productCell(ProductVariant $variant): string
    {
        $name = e($variant->product->translate('ar')?->name ?? '—');
        $url = e(route('admin.products.edit', $variant->product_id));

        return '<a href="'.$url.'" class="text-primary hover:underline">'.$name.'</a>';
    }

    private function statusBadge(ProductVariant $variant): string
    {
        $available = $variant->available_quantity;

        if ($available <= 0) {
            return $this->badge(__('admin.inventory.status_out'), 'danger');
        }

        if ($available <= $variant->low_stock_threshold) {
            return $this->badge(__('admin.inventory.status_low'), 'warning');
        }

        return $this->badge(__('admin.inventory.status_in'), 'success');
    }

    private function lastMovementCell(ProductVariant $variant): string
    {
        if (! $variant->last_movement_at) {
            return '<span class="text-muted">—</span>';
        }

        $url = e(route('admin.inventory.movements.index', ['filter' => ['variant_id' => $variant->id]]));
        $date = e(\Illuminate\Support\Carbon::parse($variant->last_movement_at)->translatedFormat('Y-m-d H:i'));

        return '<a href="'.$url.'" class="text-primary hover:underline">'.$date.'</a>';
    }

    /**
     * @return array<string, string>
     */
    private function stockStatusOptions(): array
    {
        return [
            'in' => __('admin.inventory.filter_stock_in'),
            'low' => __('admin.inventory.filter_stock_low'),
            'out' => __('admin.inventory.filter_stock_out'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return Category::withDepth()->defaultOrder()->with('translations')->get()
            ->mapWithKeys(fn (Category $category) => [
                $category->id => str_repeat('— ', $category->depth).$category->translate('ar')?->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function brandOptions(): array
    {
        return Brand::query()->with('translations')->orderBy('sort')->get()
            ->mapWithKeys(fn (Brand $brand) => [$brand->id => $brand->translate('ar')?->name])
            ->all();
    }
}
