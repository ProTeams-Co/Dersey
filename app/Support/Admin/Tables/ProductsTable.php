<?php

namespace App\Support\Admin\Tables;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Batch 3.2-A's own "does the join collide with aggregates" test case, for
 * real: withMin/withMax/withSum (variants' price range + total stock) are
 * correlated SELECT subqueries, structurally independent of whatever the
 * outer query joins - see AdminTable::applyTranslatedSort()'s own docblock
 * and tests\Feature\Admin\Table\ProductsTableAggregateSortTest for the
 * empirical proof (SQLite AND MySQL).
 *
 * Three filters (category incl. descendants, stock status, trashed-only)
 * don't fit any of AdminTable::applyFilters()'s four built-in types
 * (boolean/select/date_range/relation) - "category incl. descendants"
 * needs a nested-set id list, "stock status" needs a correlated subquery
 * comparing two aggregates, "trashed only" needs onlyTrashed(), not a
 * where(). Rather than touching AdminTable (explicitly out of scope this
 * batch) to add a new filter type for three fields, they're declared here
 * with a `type` string AdminTable's applyFilters() doesn't recognize
 * (falls through its match's `default => null`, a deliberate no-op) so
 * x-admin.filters still renders a normal <select> for them, while the
 * actual condition is applied directly in query() below, which already
 * runs before filters/sort in AdminTable::filteredQuery().
 */
class ProductsTable extends AdminTable
{
    public function columns(): array
    {
        return [
            [
                'key' => 'image',
                'label' => 'admin.products.column_image',
                'align' => 'center',
                'format' => fn () => $this->imagePlaceholder(),
            ],
            ['key' => 'name', 'label' => 'admin.products.column_name', 'sortable' => true, 'translatable' => true, 'searchable' => false],
            ['key' => 'sku', 'label' => 'admin.products.column_sku', 'searchable' => true],
            [
                'key' => 'category',
                'label' => 'admin.products.column_category',
                'format' => fn (Product $product) => e($product->primaryCategory?->translate('ar')?->name ?? '—'),
            ],
            [
                'key' => 'brand',
                'label' => 'admin.products.column_brand',
                'format' => fn (Product $product) => e($product->brand?->translate('ar')?->name ?? '—'),
            ],
            [
                'key' => 'base_price',
                'label' => 'admin.products.column_price_range',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Product $product) => $this->priceRange($product),
            ],
            [
                'key' => 'stock',
                'label' => 'admin.products.column_stock',
                'align' => 'center',
                'format' => fn (Product $product) => (string) (int) ($product->variants_sum_stock_quantity ?? 0),
            ],
            [
                'key' => 'status',
                'label' => 'admin.products.column_status',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Product $product) => $this->badge($product->status->label(), $product->status->color()),
            ],
            [
                'key' => 'created_at',
                'label' => 'admin.products.column_created_at',
                'sortable' => true,
                'format' => fn (Product $product) => $product->created_at->translatedFormat('Y-m-d'),
            ],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'category_id', 'type' => 'category_tree', 'label' => 'admin.products.filter_category', 'options' => fn () => $this->categoryOptions()],
            ['key' => 'brand_id', 'type' => 'select', 'label' => 'admin.products.filter_brand', 'column' => 'brand_id', 'options' => fn () => $this->brandOptions()],
            ['key' => 'status', 'type' => 'select', 'label' => 'admin.products.filter_status', 'column' => 'status', 'options' => fn () => $this->statusOptions()],
            ['key' => 'stock_status', 'type' => 'stock_status', 'label' => 'admin.products.filter_stock_status', 'options' => fn () => $this->stockStatusOptions()],
            ['key' => 'created_at', 'type' => 'date_range', 'label' => 'admin.products.column_created_at', 'column' => 'created_at'],
            ['key' => 'trashed', 'type' => 'trashed_only', 'label' => 'admin.products.filter_trashed', 'options' => fn () => ['1' => __('admin.products.filter_trashed')]],
        ];
    }

    /**
     * withMin/withMax read a raw COALESCE expression, not the plain
     * `price` column - product_variants.price is nullable and falls back
     * to the owning product's own base_price when unset
     * (ProductVariant::finalPrice()), so a variant that never overrides
     * its price would otherwise be silently excluded from the range
     * (SQL's MIN/MAX skip NULLs), showing "—" for products whose
     * variants all correctly rely on the fallback. The expression
     * correlates against the OUTER query's `products.base_price` via the
     * same `product_variants.product_id = products.id` correlation
     * withAggregate() already adds to the subquery's WHERE clause -
     * verified directly against MySQL (real SQL, not assumed), not just
     * reasoned about. `... as alias` on the relation name controls the
     * resulting attribute name (withAggregate()'s own syntax) - without
     * it the raw expression produces an unreadable auto-hashed key.
     */
    public function query(): Builder
    {
        $effectivePrice = DB::raw('COALESCE(product_variants.price, products.base_price)');

        $query = Product::query()
            ->withMin('variants as variants_min_price', $effectivePrice)
            ->withMax('variants as variants_max_price', $effectivePrice)
            ->withSum('variants', 'stock_quantity');

        $this->applyCategoryFilter($query);
        $this->applyStockStatusFilter($query);
        $this->applyTrashedFilter($query);

        return $query;
    }

    public function translatedSearchColumns(): array
    {
        return ['name'];
    }

    /**
     * A mandatory `id` tiebreaker on every sort - without one, two rows
     * that are equal on whatever column is being sorted (e.g. two
     * products created in the same second, or several sharing a status)
     * have no guaranteed stable order across pages, and a page fetched
     * after a concurrent insert/update can repeat or drop rows entirely.
     * Overrides filteredQuery() (public, not AdminTable itself) rather
     * than the abstract query() - orderBy() calls are additive/sequential
     * in SQL, so this only takes effect as a SECONDARY key, appended
     * after applySort() has already added the actual requested column.
     */
    public function filteredQuery(): Builder
    {
        $query = parent::filteredQuery();
        $query->orderBy('products.id', $this->currentSort()['direction']);

        return $query;
    }

    /**
     * The product's own `translations` (read by the `name` column's plain
     * data_get(), which goes through HasTranslations::getAttribute() ->
     * translate() -> $this->translations) needs eager loading same as
     * primaryCategory/brand's own translations - missing this exact
     * relation was a real bug caught by actually running the mandatory
     * aggregate/sort test against MySQL (LazyLoadingViolationException),
     * not just reasoned about in the inventory pass. Scoped to the
     * current+fallback locale only, replicating
     * HasTranslations::scopeWithCurrentTranslation()'s own logic (that
     * scope itself can't be passed directly into with([...]), it's a
     * query-builder method, not a closure).
     */
    public function with(): array
    {
        $locales = array_unique([app()->getLocale(), config('app.fallback_locale')]);

        return [
            'translations' => fn ($query) => $query->whereIn('locale', $locales),
            'primaryCategory' => fn ($query) => $query->withCurrentTranslation(),
            'brand' => fn ($query) => $query->withCurrentTranslation(),
        ];
    }

    public function defaultSort(): array
    {
        // id as an explicit tiebreaker - see currentSort()'s override
        // below, which appends it to every sort regardless of which
        // column was requested (CLAUDE.md's own "بلا tiebreaker الترقيم
        // بيكرر صفوف ويسقط تانية" requirement).
        return ['key' => 'created_at', 'direction' => 'desc'];
    }

    public function bulkActions(): array
    {
        return [
            ['key' => 'activate', 'label' => 'admin.products.bulk_activate', 'permission' => 'products.update'],
            ['key' => 'deactivate', 'label' => 'admin.products.bulk_deactivate', 'permission' => 'products.update'],
            ['key' => 'delete', 'label' => 'admin.table.actions_delete', 'permission' => 'products.delete', 'confirm' => true, 'variant' => 'danger'],
        ];
    }

    public function rowActions(): array
    {
        return [
            [
                'key' => 'edit',
                'label' => 'admin.table.actions_edit',
                'icon' => 'pencil',
                'url' => fn (Product $product) => route('admin.products.edit', $product->id),
                'permission' => 'products.update',
            ],
            [
                'key' => 'delete',
                'label' => 'admin.table.actions_delete',
                'icon' => 'trash',
                'url' => fn (Product $product) => route('admin.products.destroy', $product->id),
                'method' => 'delete',
                'permission' => 'products.delete',
                'confirm' => true,
            ],
        ];
    }

    private function imagePlaceholder(): string
    {
        // Placeholder only - the real gallery is Batch 3.2-B. Explicit
        // width/height (not just CSS sizing) so the browser reserves the
        // box before any image ever loads there - CLS, per this batch's
        // own requirement.
        return '<span class="flex h-10 w-10 items-center justify-center rounded bg-surface text-muted" style="width:2.5rem;height:2.5rem" width="40" height="40"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></span>';
    }

    /**
     * Task 3 (as clarified): base_price only - the price sort is a real
     * `products.base_price` column, never the variant min/max range. The
     * range is DISPLAY only, not sortable.
     */
    private function priceRange(Product $product): string
    {
        $min = $product->variants_min_price;
        $max = $product->variants_max_price;

        if ($min === null || $max === null) {
            return e(__('admin.products.price_range_none'));
        }

        if ((int) $min === (int) $max) {
            return e(money((int) $min));
        }

        return e(__('admin.products.price_range_dash', ['min' => money((int) $min), 'max' => money((int) $max)]));
    }

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

        $query->whereIn('primary_category_id', $ids);
    }

    /**
     * available_quantity (stock_quantity - reserved_quantity) is a
     * computed ProductVariant accessor, not a column (CLAUDE.md) - can't
     * be used in WHERE directly. Both sides are correlated scalar
     * subqueries summed across all of a product's (non-soft-deleted)
     * variants, the same technique withMin/withMax/withSum use
     * internally, just written by hand since there's no
     * whereRaw-friendly Eloquent aggregate for "available".
     */
    private function applyStockStatusFilter(Builder $query): void
    {
        $status = $this->request->input('filter.stock_status');

        if (! in_array($status, ['in', 'low', 'out'], true)) {
            return;
        }

        $available = '(select coalesce(sum(stock_quantity - reserved_quantity), 0) from product_variants '
            .'where product_variants.product_id = products.id and product_variants.deleted_at is null)';
        $threshold = '(select coalesce(sum(low_stock_threshold), 0) from product_variants '
            .'where product_variants.product_id = products.id and product_variants.deleted_at is null)';

        match ($status) {
            'out' => $query->whereRaw("{$available} <= 0"),
            'low' => $query->whereRaw("{$available} > 0")->whereRaw("{$available} <= {$threshold}"),
            'in' => $query->whereRaw("{$available} > {$threshold}"),
        };
    }

    private function applyTrashedFilter(Builder $query): void
    {
        if ($this->request->input('filter.trashed') === '1') {
            $query->onlyTrashed();
        }
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

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return collect(ProductStatus::cases())
            ->mapWithKeys(fn (ProductStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function stockStatusOptions(): array
    {
        return [
            'in' => __('admin.products.filter_stock_in'),
            'low' => __('admin.products.filter_stock_low'),
            'out' => __('admin.products.filter_stock_out'),
        ];
    }
}
