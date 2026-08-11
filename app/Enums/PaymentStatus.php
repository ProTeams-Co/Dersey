<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum PaymentStatus: string implements HasEnumOption
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid, self::PartiallyRefunded => 'warning',
            self::Paid => 'success',
            self::Refunded => 'neutral',
            self::Failed => 'danger',
        };
    }
}
