<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum DiscountType: string implements HasEnumOption
{
    case Fixed = 'fixed';
    case Percent = 'percent';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return __('enums.discount_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::FreeShipping => 'success',
            default => 'accent',
        };
    }
}
