<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum ProductStatus: string implements HasEnumOption
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __('enums.product_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Archived => 'neutral',
        };
    }
}
