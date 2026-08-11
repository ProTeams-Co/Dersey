<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

/**
 * String-backed like every other enum in the project (CLAUDE.md), even
 * though the domain value is numeric - `value` is '301'/'302', cast to
 * int only where an actual HTTP status code is needed.
 */
enum RedirectStatusCode: string implements HasEnumOption
{
    case Permanent = '301';
    case Temporary = '302';

    public function label(): string
    {
        return __('enums.redirect_status_code.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Permanent => 'neutral',
            self::Temporary => 'warning',
        };
    }

    public function toHttpCode(): int
    {
        return (int) $this->value;
    }
}
