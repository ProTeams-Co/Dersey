<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum BannerPosition: string implements HasEnumOption
{
    case Hero = 'hero';
    case Mid = 'mid';
    case Footer = 'footer';
    case Category = 'category';

    public function label(): string
    {
        return __('enums.banner_position.'.$this->value);
    }

    public function color(): string
    {
        return 'neutral';
    }
}
