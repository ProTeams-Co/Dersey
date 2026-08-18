<?php

namespace App\Support\Admin\Tables;

use App\Enums\InventoryMovementType;
use App\Models\Admin;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Batch 3.3 Task 4 - the audit log. Read-only in every sense: no row
 * action here ever leads to an edit/delete route (InventoryMovement has
 * none - see the model's own docblock). The "filtered view per variant"
 * requirement is just this same table with ?filter[variant_id]=X in the
 * URL (AdminTable's own filters are already bookmarkable/shareable by
 * design) - InventoryTable's "last movement" column links here that way,
 * not a separate page/route.
 */
class InventoryMovementsTable extends AdminTable
{
    public function columns(): array
    {
        return [
            [
                'key' => 'created_at',
                'label' => 'admin.inventory.movement_column_date',
                'sortable' => true,
                'format' => fn (InventoryMovement $movement) => e($movement->created_at->translatedFormat('Y-m-d H:i')),
            ],
            [
                'key' => 'variant',
                'label' => 'admin.inventory.movement_column_variant',
                'format' => fn (InventoryMovement $movement) => $this->variantCell($movement),
            ],
            [
                'key' => 'type',
                'label' => 'admin.inventory.movement_column_type',
                'format' => fn (InventoryMovement $movement) => $this->badge($movement->type->label(), $movement->type->color()),
            ],
            ['key' => 'quantity', 'label' => 'admin.inventory.movement_column_quantity', 'align' => 'center'],
            ['key' => 'quantity_before', 'label' => 'admin.inventory.movement_column_before', 'align' => 'center'],
            ['key' => 'quantity_after', 'label' => 'admin.inventory.movement_column_after', 'align' => 'center'],
            [
                'key' => 'admin',
                'label' => 'admin.inventory.movement_column_admin',
                'format' => fn (InventoryMovement $movement) => e($movement->admin?->name ?? __('admin.inventory.movement_admin_system')),
            ],
            [
                'key' => 'reference',
                'label' => 'admin.inventory.movement_column_reference',
                'format' => fn (InventoryMovement $movement) => $this->referenceCell($movement),
            ],
            [
                'key' => 'note',
                'label' => 'admin.inventory.movement_column_note',
                'format' => fn (InventoryMovement $movement) => e($movement->note ?? '—'),
            ],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'type', 'type' => 'select', 'label' => 'admin.inventory.movement_filter_type', 'column' => 'type', 'options' => fn () => $this->typeOptions()],
            ['key' => 'admin_id', 'type' => 'select', 'label' => 'admin.inventory.movement_filter_admin', 'column' => 'admin_id', 'options' => fn () => $this->adminOptions()],
            ['key' => 'variant_id', 'type' => 'select', 'label' => 'admin.inventory.movement_filter_variant', 'column' => 'variant_id', 'options' => fn () => $this->variantOptions()],
            ['key' => 'created_at', 'type' => 'date_range', 'label' => 'admin.inventory.movement_column_date', 'column' => 'created_at'],
        ];
    }

    public function with(): array
    {
        $locales = array_unique([app()->getLocale(), config('app.fallback_locale')]);

        return [
            // morphTo eager-loads batched by distinct reference_type, not
            // per-row - safe regardless of how many movements are in a page.
            'reference',
            'admin',
            'variant.product.translations' => fn ($query) => $query->whereIn('locale', $locales),
            'variant.attributeValues.attribute',
            'variant.attributeValues.translations' => fn ($query) => $query->whereIn('locale', $locales),
        ];
    }

    public function query(): Builder
    {
        return InventoryMovement::query();
    }

    public function defaultSort(): array
    {
        return ['key' => 'created_at', 'direction' => 'desc'];
    }

    /**
     * 50, matching InventoryTable's own reasoning - this batch's N+1
     * requirement measures 50 movements on one page.
     */
    public function perPage(): int
    {
        return 50;
    }

    /**
     * id tiebreaker, same reasoning/pattern as ProductsTable/InventoryTable -
     * "the newest first" only has second-resolution timestamps, so two
     * movements in the same second need a stable secondary key too.
     */
    public function filteredQuery(): Builder
    {
        $query = parent::filteredQuery();
        $query->orderBy('inventory_movements.id', $this->currentSort()['direction']);

        return $query;
    }

    private function variantCell(InventoryMovement $movement): string
    {
        $variant = $movement->variant;

        if (! $variant) {
            return '<span class="text-muted">—</span>';
        }

        $product = e($variant->product->translate('ar')?->name ?? '—');
        $options = e($variant->optionsLabel('ar'));
        $sku = e($variant->sku);

        return "<div>{$product} <span class=\"text-muted\">({$sku})</span></div><div class=\"text-xs text-muted\">{$options}</div>";
    }

    private function referenceCell(InventoryMovement $movement): string
    {
        if (! $movement->reference_type) {
            return '<span class="text-muted">—</span>';
        }

        if ($movement->reference_type === Order::class && $movement->reference) {
            return e('#'.$movement->reference->order_number);
        }

        if ($movement->reference_type === ProductVariant::class) {
            return e(__('admin.inventory.reference_initial_stock'));
        }

        return '<span class="text-muted">—</span>';
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        return collect(InventoryMovementType::cases())
            ->mapWithKeys(fn (InventoryMovementType $type) => [$type->value => $type->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function adminOptions(): array
    {
        return Admin::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function variantOptions(): array
    {
        return ProductVariant::query()
            ->with(['product.translations'])
            ->orderBy('sku')
            ->get()
            ->mapWithKeys(fn (ProductVariant $variant) => [
                $variant->id => ($variant->product->translate('ar')?->name ?? '—').' — '.$variant->sku,
            ])
            ->all();
    }
}
