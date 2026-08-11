<?php

namespace App\Enums\Contracts;

/**
 * Every backed enum in App\Enums implements this — a raw ->value is never
 * shown to a user (label() goes through lang/{ar,en}/enums.php) and never
 * styled by hand (color() returns one of x-ui.badge's own variant names:
 * neutral/success/warning/danger/accent), so a Blade view never has to know
 * which enum it's holding to render either one consistently.
 */
interface HasEnumOption
{
    public function label(): string;

    public function color(): string;
}
