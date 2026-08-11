<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum Gender: string implements HasEnumOption
{
    case Men = 'men';
    case Women = 'women';
    case Unisex = 'unisex';
    case Kids = 'kids';

    public function label(): string
    {
        return __('enums.gender.'.$this->value);
    }

    public function color(): string
    {
        // No risk/status semantics apply to a gender value — every case
        // gets the same neutral badge rather than an arbitrary color.
        return 'neutral';
    }
}
