<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum PaymentMethod: string implements HasEnumOption
{
    case Card = 'card';
    case Wallet = 'wallet';
    case Kiosk = 'kiosk';
    case Valu = 'valu';
    case CashOnDelivery = 'cod';

    public function label(): string
    {
        return __('enums.payment_method.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'neutral',
            default => 'accent',
        };
    }
}
