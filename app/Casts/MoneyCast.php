<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Money, Money|int>
 *
 * set() accepts exactly two input shapes, both unambiguous:
 *   - a real Money instance (->minor() is stored)
 *   - a raw int, ALWAYS piasters/minor units - this matches every
 *     factory in the project (`fake()->numberBetween(15000, 300000)`,
 *     `(int) round($basePrice * 0.55)`, ...), never major units.
 *
 * Anything else (string, float) is rejected loudly with
 * InvalidArgumentException, not silently coerced. Batch 3.2-M's own
 * reason for existing: a raw major-unit decimal string like "199.50"
 * handed to the old set() went through a bare (int) cast and silently
 * truncated to 199 (piasters, i.e. 1.99 EGP instead of 199.50 EGP) -
 * caught only via a live admin session, not by the 206-test suite that
 * existed at the time, because nothing exercised this exact input shape.
 * A caller with a major-unit string must convert it via
 * Money::fromMajor() itself before this cast ever sees it - see
 * ProductsController::convertPriceFields() and
 * ProductVariantMatrixService's identical helper for the one shared
 * place that conversion happens.
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        return is_null($value) ? null : Money::fromMinor((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->minor();
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf(
            'Column [%s] on [%s] received an ambiguous money value of type %s (%s). '
            .'MoneyCast only accepts a App\Support\Money instance, or a raw int (always '
            .'piasters/minor units - matching every factory in this project). A major-unit '
            .'decimal string like "199.50" must be converted via Money::fromMajor() before '
            .'assignment; handing it here directly would silently truncate the fractional part.',
            $key,
            get_class($model),
            get_debug_type($value),
            is_scalar($value) ? var_export($value, true) : '(non-scalar)',
        ));
    }
}
