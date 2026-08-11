<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum RefundStatus: string implements HasEnumOption
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.refund_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processed => 'success',
            self::Failed => 'danger',
        };
    }
}
