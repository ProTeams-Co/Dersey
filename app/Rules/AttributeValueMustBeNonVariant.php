<?php

namespace App\Rules;

use App\Models\AttributeValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * product_attribute_value has no DB-level is_variant constraint (see its
 * migration's own comment) - a variant-generating attribute's value (size,
 * color) is only ever meant to reach a product through product_variants
 * (Batch 3.2-B), never as a general, non-variant product attribute. Without
 * this rule, ProductsController's "attribute_value_ids" field (the
 * Attributes tab) would silently accept one anyway.
 *
 * Existence itself (attribute_value_ids.* also carries `exists:attribute_values,id`)
 * is a separate rule - this one only cares about is_variant once the id is
 * known to exist, so it silently passes a not-found id and lets the exists
 * rule report that failure instead.
 */
class AttributeValueMustBeNonVariant implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isVariant = AttributeValue::whereKey($value)->with('attribute')->first()?->attribute?->is_variant;

        if ($isVariant === true) {
            $fail(__('errors.attribute_value_must_be_non_variant'));
        }
    }
}
