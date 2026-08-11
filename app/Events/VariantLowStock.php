<?php

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InventoryService whenever a stock-reducing movement
 * leaves a variant at or under its low_stock_threshold. Deliberately just
 * the event - no listener exists yet ("Event بس، مش إيميل دلوقتي" from
 * the batch spec); a future batch attaches whatever should react to it
 * (admin dashboard badge, email, ...).
 */
class VariantLowStock
{
    use Dispatchable;

    public function __construct(public readonly ProductVariant $variant)
    {
    }
}
