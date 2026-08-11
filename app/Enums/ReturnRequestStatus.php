<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum ReturnRequestStatus: string implements HasEnumOption
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return __('enums.return_request_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'accent',
            self::Rejected => 'danger',
            self::Completed => 'success',
        };
    }
}
