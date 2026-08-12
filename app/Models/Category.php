<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use App\Support\Traits\HasSeo;
use App\Support\Traits\HasTranslations;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;

/**
 * Deleting a category with live children is blocked entirely (see
 * CategoryObserver) rather than left to kalnoy/nestedset's own default
 * cascade-soft-delete-the-whole-subtree behavior - an approved decision,
 * not an assumption. _lft/_rgt integrity itself is already handled
 * correctly by the package regardless (soft-deleting a leaf never
 * renumbers surviving siblings; renumbering only happens on forceDelete).
 */
#[ObservedBy([CategoryObserver::class])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSeo, HasTranslations, NodeTrait, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'image',
        'icon',
        'sort',
        'is_active',
        'is_featured',
        'show_in_menu',
    ];

    protected array $translatable = ['name', 'slug', 'description', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return CategoryTranslation::class;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    /**
     * Thin wrapper over the package's own whereIsRoot() - named to match
     * this batch's requested API rather than reimplementing it.
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereIsRoot();
    }

    /**
     * Root-to-self chain, in order - ancestors() already orders by tree
     * position (root first), so appending self is all that is needed.
     * withCurrentTranslation() avoids lazy-loading each ancestor's
     * translation individually when the caller displays the breadcrumb's
     * names right after (preventLazyLoading is on outside production, so
     * that would throw rather than just silently N+1).
     */
    public function breadcrumb(): Collection
    {
        return $this->ancestors()->withCurrentTranslation()->get()->push($this);
    }

    /**
     * TODO(4.x): the only place to change when real category show routes
     * exist. The SEO plan calls for a flat path (/{locale}/{category-slug}),
     * not this placeholder - update the return here to match, and backfill
     * every existing `redirects` row that was generated from the old
     * convention (see CLAUDE.md's "التوجيهات التلقائية" note).
     */
    public static function seoPath(string $slug, string $locale): string
    {
        return "/{$locale}/categories/{$slug}";
    }

    public function defaultSeoTitle(?string $locale = null): string
    {
        return $this->translate($locale)?->name ?? '';
    }

    public function defaultSeoDescription(?string $locale = null): ?string
    {
        $description = $this->translate($locale)?->description;

        return $description ? Str::limit(strip_tags($description), 160) : null;
    }

    public function defaultSeoImage(): ?string
    {
        return $this->image;
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaMarkup(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $items = $this->breadcrumb()->values()->map(function (self $category, int $index) use ($locale) {
            $translation = $category->translate($locale);

            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $translation?->name,
                'item' => url("/{$locale}/categories/{$translation?->slug}"),
            ];
        })->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * True when nothing currently blocks delete() from succeeding - lets a
     * future admin UI disable the delete button up front instead of letting
     * the user click it and land on CategoryHasChildrenException's error.
     */
    public function canBeDeleted(): bool
    {
        return $this->deletionBlockers() === [];
    }

    /**
     * Translation keys (not raw text - see CLAUDE.md's "no hardcoded text
     * in Blade" rule) for every reason delete() would currently refuse this
     * category, so a caller can render all of them (e.g. as button tooltips)
     * rather than learning about them one at a time via caught exceptions.
     *
     * Both checks are "block outright" by design (approved decision, same
     * policy for both) - neither auto-unlinks products from category_product
     * nor auto-moves them to a parent category on delete.
     *
     * @return list<string>
     */
    public function deletionBlockers(): array
    {
        return $this->deletionBlockersFor(
            $this->children()->exists(),
            $this->products()->exists(),
        );
    }

    /**
     * Same blocker keys as deletionBlockers(), but takes both facts as
     * already-known booleans instead of running its own two exists()
     * queries - added when the admin tree view (Batch 3.1) needed this for
     * every visible node without turning into a 2-queries-per-node N+1.
     * The tree view already knows "has children" for free (toTree()'s
     * relation is loaded in memory) and "has direct products" from one bulk
     * query for the whole tree - see CategoriesController::index().
     *
     * @return list<string>
     */
    public function deletionBlockersFor(bool $hasChildren, bool $hasDirectProducts): array
    {
        $blockers = [];

        if ($hasChildren) {
            $blockers[] = 'errors.category_has_children';
        }

        if ($hasDirectProducts) {
            $blockers[] = 'errors.category_has_products';
        }

        return $blockers;
    }

    /**
     * Products in this category AND every live descendant category,
     * counted once each even if a product sits in more than one of them.
     * descendants() already excludes soft-deleted categories (SoftDeletes'
     * global scope applies to the relation query like any other), so a
     * deleted branch's products silently stop counting here without any
     * extra filtering.
     */
    public function descendantProductsCount(): int
    {
        $categoryIds = $this->descendants()->pluck('id')->push($this->id);

        return Product::whereHas(
            'categories',
            fn (Builder $query) => $query->whereIn('categories.id', $categoryIds)
        )->count();
    }

    /**
     * The same "products in this category and every descendant, counted
     * once" as descendantProductsCount() - but for every category at
     * once, in exactly 2 queries total, instead of one call to
     * descendantProductsCount() per node (N+1 for a whole tree/table
     * render, added in Batch 3.1 when the admin category screens needed
     * this for every visible row without violating the project's own
     * fixed-query-count rule). Keyed by category id.
     *
     * @return BaseCollection<int, int>
     */
    public static function cumulativeProductCounts(): BaseCollection
    {
        $categories = static::query()->get(['id', '_lft', '_rgt']);
        $pivotRows = DB::table('category_product')->select('category_id', 'product_id')->get();

        return $categories->mapWithKeys(function (self $node) use ($categories, $pivotRows) {
            $subtreeIds = $categories
                ->filter(fn (self $c) => $c->_lft >= $node->_lft && $c->_rgt <= $node->_rgt)
                ->pluck('id');

            $count = $pivotRows->whereIn('category_id', $subtreeIds)->pluck('product_id')->unique()->count();

            return [$node->id => $count];
        });
    }
}
