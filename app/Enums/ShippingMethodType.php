<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum ShippingMethodType: string implements HasEnumOption
{
    case Flat = 'flat';
    case WeightBased = 'weight_based';
    case FreeOver = 'free_over';

    public function label(): string
    {
        return __('enums.shipping_method_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Flat => 'neutral',
            self::WeightBased => 'accent',
            self::FreeOver => 'success',
        };
    }
}
