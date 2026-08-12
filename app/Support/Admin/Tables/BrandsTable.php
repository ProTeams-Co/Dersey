<?php

namespace App\Support\Admin\Tables;

use App\Models\Brand;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * The batch's control screen - deliberately built as plainly as possible
 * against AdminTable/AdminController, no custom query/rendering beyond
 * what a translatable, non-hierarchical resource genuinely needs. See
 * CategoriesTable (tree, drag-drop, custom search) for the contrast.
 */
class BrandsTable extends AdminTable
{
    public function columns(): array
    {
        return [
            [
                'key' => 'logo',
                'label' => 'admin.brands.column_logo',
                'align' => 'center',
                'format' => fn (Brand $brand) => $this->logoThumbnail($brand),
            ],
            // translatable: true - 'name' lives on brand_translations, not
            // `brands` itself. AdminTable::applyTranslatedSort() auto-joins
            // the translation table for this instead of a plain orderBy(),
            // which used to throw "Unknown column" on MySQL.
            ['key' => 'name', 'label' => 'admin.brands.column_name', 'sortable' => true, 'translatable' => true, 'searchable' => true],
            [
                'key' => 'products_count',
                'label' => 'admin.brands.column_products_count',
                'align' => 'center',
                'format' => fn (Brand $brand) => (string) $brand->products_count,
            ],
            [
                'key' => 'is_active',
                'label' => 'admin.brands.column_status',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Brand $brand) => $this->booleanBadge($brand->is_active),
            ],
            ['key' => 'sort', 'label' => 'admin.brands.column_sort', 'sortable' => true, 'align' => 'center'],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'key' => 'is_active',
                'type' => 'boolean',
                'label' => 'admin.brands.column_status',
            ],
        ];
    }

    public function query(): Builder
    {
        return Brand::query()->withCount('products');
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
    public function bulkActions(): array
    {
        return [
            ['key' => 'activate', 'label' => 'admin.brands.bulk_activate', 'permission' => 'brands.update'],
            ['key' => 'deactivate', 'label' => 'admin.brands.bulk_deactivate', 'permission' => 'brands.update'],
            ['key' => 'delete', 'label' => 'admin.table.actions_delete', 'permission' => 'brands.delete', 'confirm' => true, 'variant' => 'danger'],
        ];
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
                'url' => fn (Brand $brand) => route('admin.brands.edit', $brand->id),
                'permission' => 'brands.update',
            ],
            [
                'key' => 'delete',
                'label' => 'admin.table.actions_delete',
                'icon' => 'trash',
                'url' => fn (Brand $brand) => route('admin.brands.destroy', $brand->id),
                'method' => 'delete',
                'permission' => 'brands.delete',
                'confirm' => true,
            ],
        ];
    }

    private function logoThumbnail(Brand $brand): string
    {
        if (! $brand->logo) {
            return '<span class="flex h-8 w-16 items-center justify-center rounded bg-surface text-xs text-muted">—</span>';
        }

        // Storage::disk('public') explicitly - the bare Storage::url()
        // facade shortcut resolves against the DEFAULT disk
        // (FILESYSTEM_DISK=local in .env), not `public`. `local`'s
        // built-in serve route requires a signed URL unless visibility is
        // explicitly public (verified directly: a plain unsigned URL
        // 403'd) - this is the exact bug already fixed once for this same
        // method; Storage::url() alone would have silently reintroduced it.
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $url = e($disk->url($brand->logo));

        // object-contain, not object-cover: a brand logo is typically a
        // wide wordmark, not a square photo - cover-cropping it into a
        // tight 32x32 square sliced off most of the actual logo, leaving
        // only a sliver visible (read as "squished/unclear", but it was
        // really just cropping working as designed on the wrong shape of
        // image). contain shows the whole logo, letterboxed, inside a
        // wider box that better fits a wordmark's aspect ratio.
        return '<img src="'.$url.'" alt="" class="h-8 w-16 rounded bg-surface object-contain p-1">';
    }
}
