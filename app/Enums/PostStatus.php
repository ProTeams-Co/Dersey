<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum PostStatus: string implements HasEnumOption
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return __('enums.post_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Scheduled => 'accent',
        };
    }
}
