<?php

use App\Support\Money;

if (! function_exists('money')) {
    function money(Money|int $value, ?string $locale = null): string
    {
        $money = $value instanceof Money ? $value : Money::fromMinor($value);

        return $money->format($locale ?? app()->getLocale());
    }
}
