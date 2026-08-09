<?php

use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Western Arabic digits (0123456789) for both locales — not Hindi-Arabic
 * (٠١٢٣٤٥٦٧٨٩). See the Batch 1.3 output notes for the full reasoning;
 * short version: it's what Carbon's own 'ar' locale data already produces
 * for translatedFormat() with zero extra conversion, and forcing ICU's
 * ar_EG@numbers=latn variant (rather than its ar_EG default, which IS
 * Hindi-Arabic) lines up with the "1,234.00 ج.م" example in the batch spec
 * itself — comma thousands separator, period decimal, Western digits.
 */
if (! function_exists('format_number')) {
    function format_number(int|float $value, ?string $locale = null, int $decimals = 0): string
    {
        $locale = $locale ?? app()->getLocale();

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter(
                $locale === 'ar' ? 'ar_EG@numbers=latn' : 'en_GB',
                NumberFormatter::DECIMAL
            );
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            return $formatter->format($value);
        }

        return number_format($value, $decimals, '.', ',');
    }
}

if (! function_exists('format_currency')) {
    function format_currency(Money|int $value, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $money = $value instanceof Money ? $value : Money::fromMinor($value);

        // Money::format() already owns the amount (rounding/minor-unit math)
        // and the prefix/suffix placement per locale — this only adds
        // thousands grouping to the integer part of what it returned, it
        // never recomputes the amount itself.
        return preg_replace_callback(
            '/\d+(?=\.\d{2})/',
            fn (array $match) => format_number((int) $match[0], $locale),
            $money->format($locale)
        );
    }
}

if (! function_exists('format_date')) {
    function format_date(DateTimeInterface $date, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        $carbon = $date instanceof CarbonInterface ? $date : Carbon\Carbon::instance($date);

        return $carbon->locale($locale)->translatedFormat('j F Y');
    }
}
