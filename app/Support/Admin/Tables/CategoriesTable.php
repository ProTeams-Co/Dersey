<?php

namespace App\Support\Admin\Tables;

use App\Models\Category;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Batch 3.1 gap: AdminTable is fundamentally a flat, paginated-rows
 * engine - it has no concept of parent/child at all. A category tree
 * (indentation by depth, expand/collapse, drag-and-drop re-parenting)
 * doesn't fit that shape, and forcing it to would mean either fake
 * pagination over a tree (meaningless - you want the whole tree, not
 * page 1 of 20) or reimplementing tree logic inside AdminTable itself for
 * a need only this one screen has.
 *
 * So this class is only used for the two things that genuinely ARE
 * flat/tabular even for a hierarchical resource: CSV export (a spreadsheet
 * has no tree structure either) and the "search across the whole tree"
 * mode - CategoriesController::index() renders the real recursive tree
 * view when there's no search term, and falls back to this table's own
 * flat, matches-only rendering when there is one (see that controller's
 * docblock for why flattening search results was chosen over the more
 * complex "matches plus their ancestors, still nested" alternative).
 */
class CategoriesTable extends AdminTable
{
    private ?Collection $cumulativeCounts = null;

    /**
     * Memoized per request, computed once regardless of how many rows are
     * being rendered - see Category::cumulativeProductCounts()'s docblock.
     *
     * @return Collection<int, int>
     */
    private function cumulativeCounts(): Collection
    {
        return $this->cumulativeCounts ??= Category::cumulativeProductCounts();
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'image',
                'label' => 'admin.categories.column_image',
                'align' => 'center',
                'format' => fn (Category $category) => $this->imageThumbnail($category),
            ],
            // translatable: true - see BrandsTable::columns()'s docblock on
            // the same 'name' column / AdminTable::applyTranslatedSort().
            ['key' => 'name', 'label' => 'admin.categories.column_name', 'sortable' => true, 'translatable' => true, 'searchable' => true],
            [
                'key' => 'products_count',
                'label' => 'admin.categories.column_products_count',
                'align' => 'center',
                'format' => fn (Category $category) => (string) $this->cumulativeCounts()->get($category->id, 0),
            ],
            [
                'key' => 'is_active',
                'label' => 'admin.categories.column_status',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Category $category) => $this->booleanBadge($category->is_active),
            ],
            ['key' => 'sort', 'label' => 'admin.categories.column_sort', 'sortable' => true, 'align' => 'center'],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'is_active', 'type' => 'boolean', 'label' => 'admin.categories.column_status'],
        ];
    }

    public function query(): Builder
    {
        return Category::query();
    }

    public function translatedSearchColumns(): array
    {
        return ['name'];
    }

    public function with(): array
    {
        return ['translations'];
    }

    public function defaultSort(): array
    {
        return ['key' => 'sort', 'direction' => 'asc'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rowActions(): array
    {
        return [
            [
                'key' => 'edit',
                'label' => 'admin.table.actions_edit',
                'icon' => 'pencil',
                'url' => fn (Category $category) => route('admin.categories.edit', $category->id),
                'permission' => 'categories.update',
            ],
            [
                'key' => 'delete',
                'label' => 'admin.table.actions_delete',
                'icon' => 'trash',
                'url' => fn (Category $category) => route('admin.categories.destroy', $category->id),
                'method' => 'delete',
                'permission' => 'categories.delete',
                'confirm' => true,
            ],
        ];
    }

    private function imageThumbnail(Category $category): string
    {
        if (! $category->image) {
            return '<span class="flex h-8 w-8 items-center justify-center rounded bg-surface text-xs text-muted">—</span>';
        }

        $url = e(Storage::disk('public')->url($category->image));

        return '<img src="'.$url.'" alt="" class="h-8 w-8 rounded object-cover" width="32" height="32">';
    }
}
