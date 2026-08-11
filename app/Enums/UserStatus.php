<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum UserStatus: string implements HasEnumOption
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Banned = 'banned';

    public function label(): string
    {
        return __('enums.user_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'neutral',
            self::Banned => 'danger',
        };
    }
}
