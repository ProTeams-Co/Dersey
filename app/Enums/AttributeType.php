<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum AttributeType: string implements HasEnumOption
{
    case Select = 'select';
    case Color = 'color';
    case Text = 'text';

    public function label(): string
    {
        return __('enums.attribute_type.'.$this->value);
    }

    public function color(): string
    {
        return 'neutral';
    }
}
