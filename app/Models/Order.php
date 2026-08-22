<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * No SoftDeletes - orders are never deleted (CLAUDE.md: financial records
 * have no delete path at all, cancellation is a status). Status
 * transitions must go through App\Services\Order\OrderService::
 * transitionTo(), never a direct ->update(['status' => ...]) - see that
 * service for why (OrderStatus::canTransitionTo() + an
 * order_status_histories row on every change).
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'guest_phone',
        'status',
        'payment_status',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'grand_total',
        'coupon_id',
        'coupon_code',
        'currency',
        'payment_method',
        'shipping_address',
        'billing_address',
        'shipping_method_name',
        'customer_note',
        'admin_note',
        'locale',
        'ip',
        'user_agent',
        'placed_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'shipping_total' => MoneyCast::class,
            'tax_total' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
            'payment_method' => PaymentMethod::class,
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'shipping_method_name' => 'array',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * Batch 3.4 - the inverse of InventoryMovement::reference() (a plain
     * morphTo(), default column names), for the order-detail screen's own
     * "حركات المخزون المرتبطة" section. Deliberately scoped to movements
     * that reference THIS order directly (restoreInventory()'s cancellation
     * restocks, and InventoryService::commit() during checkout) - not
     * every movement ever logged against the order's variants, which could
     * include unrelated manual adjustments from the Inventory screen that
     * have nothing to do with this specific order.
     */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
