<?php

namespace App\Rules;

use App\Models\AttributeValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The mirror of App\Rules\AttributeValueMustBeNonVariant, for the variant
 * matrix's own inputs (ProductVariantsController's preview()/generate()) -
 * an attribute value submitted as a variant axis must actually belong to
 * an is_variant = true attribute, or ProductVariant::syncOptionValues()
 * would reject it deep inside the service with a raw InvalidArgumentException
 * instead of a clean 422 at the validation boundary.
 *
 * Existence itself is a separate rule (`exists:attribute_values,id`) - this
 * one only cares about is_variant once the id is known to exist.
 */
class AttributeValueMustBeVariant implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isVariant = AttributeValue::whereKey($value)->with('attribute')->first()?->attribute?->is_variant;

        if ($isVariant === false) {
            $fail(__('errors.attribute_value_must_be_variant'));
        }
    }
}
