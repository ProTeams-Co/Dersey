<?php

namespace App\Support\Admin\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Batch 3.4 - read-only list (no bulk actions: a bulk status change across
 * orders is explicitly out of scope, per this batch's own "خطر" note - a
 * wrong status jump touches inventory AND money at once). Search spans
 * order_number/guest_email/the related user's name - three different
 * shapes (own column, own column, related model column) that don't fit
 * AdminTable's generic `searchable`/translatedSearchColumns() (built for
 * one model's own columns/translations, not a plain related column), same
 * reasoning InventoryTable::applyProductSearch() already established for
 * its own SKU-or-product-name OR search.
 */
class OrdersTable extends AdminTable
{
    public function columns(): array
    {
        return [
            [
                'key' => 'order_number',
                'label' => 'admin.orders.column_number',
                'sortable' => true,
                'format' => fn (Order $order) => $this->numberCell($order),
            ],
            [
                'key' => 'customer',
                'label' => 'admin.orders.column_customer',
                'format' => fn (Order $order) => e($order->user?->name ?? $order->guest_email ?? '—'),
            ],
            [
                'key' => 'status',
                'label' => 'admin.orders.column_status',
                'align' => 'center',
                'format' => fn (Order $order) => $this->badge($order->status->label(), $order->status->color()),
            ],
            [
                'key' => 'payment_status',
                'label' => 'admin.orders.column_payment_status',
                'align' => 'center',
                'format' => fn (Order $order) => $this->badge($order->payment_status->label(), $order->payment_status->color()),
            ],
            [
                'key' => 'grand_total',
                'label' => 'admin.orders.column_total',
                'sortable' => true,
                'align' => 'end',
                'format' => fn (Order $order) => money($order->grand_total),
            ],
            [
                'key' => 'items_count',
                'label' => 'admin.orders.column_items_count',
                'align' => 'center',
                'format' => fn (Order $order) => (string) $order->items_count,
            ],
            [
                'key' => 'placed_at',
                'label' => 'admin.orders.column_placed_at',
                'sortable' => true,
                'format' => fn (Order $order) => $order->placed_at?->translatedFormat('Y-m-d H:i') ?? '—',
            ],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'key' => 'status', 'type' => 'select', 'label' => 'admin.orders.filter_status', 'column' => 'status',
                'options' => fn () => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])->all(),
            ],
            [
                'key' => 'payment_status', 'type' => 'select', 'label' => 'admin.orders.filter_payment_status', 'column' => 'payment_status',
                'options' => fn () => collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])->all(),
            ],
            [
                'key' => 'payment_method', 'type' => 'select', 'label' => 'admin.orders.filter_payment_method', 'column' => 'payment_method',
                'options' => fn () => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()])->all(),
            ],
            ['key' => 'placed_at', 'type' => 'date_range', 'label' => 'admin.orders.column_placed_at', 'column' => 'placed_at'],
        ];
    }

    public function rowActions(): array
    {
        return [
            [
                'key' => 'show',
                'label' => 'admin.orders.view_button',
                'icon' => 'menu',
                'url' => fn (Order $order) => route('admin.orders.show', $order->id),
                'permission' => 'orders.view',
            ],
        ];
    }

    public function with(): array
    {
        return ['user'];
    }

    public function query(): Builder
    {
        $query = Order::query()->withCount('items');

        $this->applyCustomerSearch($query);

        return $query;
    }

    public function defaultSort(): array
    {
        return ['key' => 'placed_at', 'direction' => 'desc'];
    }

    public function perPage(): int
    {
        return 50;
    }

    /**
     * id tiebreaker - same pattern as ProductsTable::filteredQuery()/
     * InventoryTable::filteredQuery() (append AFTER applySort() has
     * already added the requested column, so it only ever acts as a
     * secondary key).
     */
    public function filteredQuery(): Builder
    {
        $query = parent::filteredQuery();
        $query->orderBy('orders.id', $this->currentSort()['direction']);

        return $query;
    }

    /**
     * order_number/guest_email (own columns) OR the related user's name -
     * one where() so all three are OR'd together, not separately AND'd.
     */
    private function applyCustomerSearch(Builder $query): void
    {
        $term = $this->currentSearch();

        if ($term === null || $term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('guest_email', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', "%{$term}%"));
        });
    }

    private function numberCell(Order $order): string
    {
        $url = e(route('admin.orders.show', $order->id));

        return '<a href="'.$url.'" class="text-primary hover:underline" dir="ltr">'.e($order->order_number).'</a>';
    }
}
