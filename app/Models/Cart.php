<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
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
        return $this->hasMany(CartItem::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Sum of unit_price * quantity across every item, at the price each
     * one was added at - not the current live price (see
     * hasPriceChanges()). Reads from the already-loaded `items` relation;
     * eager-load it before calling this to avoid a lazy load.
     */
    public function subtotal(): Money
    {
        return $this->items->reduce(
            fn (Money $total, CartItem $item) => $total->add(
                Money::fromMinor($item->unit_price->minor() * $item->quantity)
            ),
            Money::zero()
        );
    }

    /**
     * The items whose stored unit_price no longer matches the variant's
     * current final_price - what the checkout screen warns the customer
     * about before charging them the up-to-date amount. Reads from
     * already-loaded relations (items.variant.product); eager-load both
     * before calling.
     */
    public function hasPriceChanges(): Collection
    {
        return $this->items->filter(
            fn (CartItem $item) => ! $item->unit_price->equals($item->variant->final_price)
        )->values();
    }
}
