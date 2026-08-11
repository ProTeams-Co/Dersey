<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum InventoryMovementType: string implements HasEnumOption
{
    case In = 'in';
    case Out = 'out';
    case Reserve = 'reserve';
    case Release = 'release';
    case Adjust = 'adjust';

    public function label(): string
    {
        return __('enums.inventory_movement_type.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'warning',
            self::Reserve => 'accent',
            self::Release, self::Adjust => 'neutral',
        };
    }
}
