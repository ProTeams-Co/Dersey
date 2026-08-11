<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DiscountType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `value` is a plain integer, not MoneyCast - its meaning depends on
 * `type` (piasters for Fixed, a raw 0-100 percentage for Percent,
 * irrelevant for FreeShipping). See the migration's own note.
 * App\Services\Coupon\CouponService is the only place that interprets it.
 */
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'first_order_only',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'min_order_amount' => MoneyCast::class,
            'max_discount_amount' => MoneyCast::class,
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'first_order_only' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function couponables(): HasMany
    {
        return $this->hasMany(Couponable::class);
    }

    public function isRestricted(): bool
    {
        return $this->couponables()->exists();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasNotStartedYet(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
