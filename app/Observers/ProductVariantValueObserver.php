<?php

namespace App\Observers;

use App\Models\AttributeValue;
use App\Models\ProductVariantValue;
use InvalidArgumentException;

/**
 * Backstop for ProductVariant::syncOptionValues()'s own validation - runs
 * on every product_variant_values row creation regardless of entry point,
 * so a raw ->variantValues()->create()/attach() call that skips
 * syncOptionValues() still can't produce an invalid row.
 *
 * Only re-checks the two rules that are checkable from a single row in
 * isolation (is_variant = true, no duplicate attribute within the same
 * variant). The third rule from the batch spec - every variant of a
 * product must share the same *set* of attributes as its siblings - needs
 * to see a variant's whole finished option set, not one pivot row at a
 * time, so it only lives in syncOptionValues().
 */
class ProductVariantValueObserver
{
    public function creating(ProductVariantValue $variantValue): void
    {
        $value = AttributeValue::with('attribute')->findOrFail($variantValue->attribute_value_id);

        if (! $value->attribute->is_variant) {
            throw new InvalidArgumentException(
                "Attribute value #{$value->id} belongs to a non-variant attribute (is_variant = false) and cannot be used on a product variant."
            );
        }

        $duplicateAttribute = ProductVariantValue::query()
            ->where('variant_id', $variantValue->variant_id)
            ->whereHas('attributeValue', fn ($query) => $query->where('attribute_id', $value->attribute_id))
            ->exists();

        if ($duplicateAttribute) {
            throw new InvalidArgumentException(
                "This variant already has a value for attribute #{$value->attribute_id} - a variant cannot have two values from the same attribute."
            );
        }
    }
}
