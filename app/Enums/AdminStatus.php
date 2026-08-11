<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum AdminStatus: string implements HasEnumOption
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('enums.admin_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'neutral',
        };
    }
}
