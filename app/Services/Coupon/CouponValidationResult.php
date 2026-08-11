<?php

namespace App\Services\Coupon;

/**
 * validate() needs to return both a yes/no AND, when it's "no", why -
 * a plain bool can't carry the reason, so this is the smallest type that
 * can. `reason` is a translation key (lang/{ar,en}/errors.php), not raw
 * text, matching the rest of the project's exception-message convention.
 */
final readonly class CouponValidationResult
{
    private function __construct(
        public bool $valid,
        public ?string $reason = null,
    ) {
    }

    public static function valid(): self
    {
        return new self(true);
    }

    public static function invalid(string $reason): self
    {
        return new self(false, $reason);
    }
}
