<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum ContactMessageStatus: string implements HasEnumOption
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';

    public function label(): string
    {
        return __('enums.contact_message_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'accent',
            self::Read => 'neutral',
            self::Replied => 'success',
        };
    }
}
