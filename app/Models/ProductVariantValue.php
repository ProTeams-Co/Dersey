<?php

namespace App\Models;

use App\Observers\ProductVariantValueObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pure pivot row (product_variant_values) - no timestamps, matching the
 * other hard-delete linking tables from Batch 2.2 (category_product,
 * product_attribute_value). Almost always written through
 * ProductVariant::syncOptionValues(), never directly - see
 * ProductVariantValueObserver for what still applies if something writes
 * to it directly anyway.
 */
#[ObservedBy([ProductVariantValueObserver::class])]
class ProductVariantValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'attribute_value_id',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
