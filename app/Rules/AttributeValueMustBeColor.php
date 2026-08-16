<?php

namespace App\Rules;

use App\Enums\AttributeType;
use App\Models\AttributeValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Batch 3.2-C decision B - product_images.color_value_id accepts any
 * attribute_value_id at the DB level (nullable FK to attribute_values, no
 * type check possible in SQL) - without this rule, ProductImagesController
 * would let an admin link a gallery image to a "size" or "material" value
 * just as easily as an actual color, with nothing ever catching it.
 *
 * Same pattern as AttributeValueMustBeVariant/AttributeValueMustBeNonVariant:
 * existence is a separate rule (`exists:attribute_values,id`), and a
 * not-found id is silently allowed to pass here so the exists rule reports
 * that failure instead. "Is this a color" is resolved via
 * Attribute::colorAttribute() (decision A) - the attribute's type, not a
 * hardcoded code string.
 */
class AttributeValueMustBeColor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $type = AttributeValue::whereKey($value)->with('attribute')->first()?->attribute?->type;

        if ($type !== null && $type !== AttributeType::Color) {
            $fail(__('errors.attribute_value_must_be_color'));
        }
    }
}
