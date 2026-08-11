<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable snapshot, not a live reference - see the migration's
 * docblock. product_name/variant_options/sku/image_path/unit_price/
 * line_total are the source of truth for displaying this order, always,
 * even after the product/variant they were copied from is renamed,
 * repriced, or deleted. product()/variant() below are conveniences for
 * when the catalog row still happens to exist (e.g. linking back to it),
 * never for re-deriving what to display.
 *
 * No timestamps ($timestamps = false) - see the migration.
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_options',
        'sku',
        'image_path',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'product_name' => 'array',
            'variant_options' => 'array',
            'unit_price' => MoneyCast::class,
            'quantity' => 'integer',
            'line_total' => MoneyCast::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
