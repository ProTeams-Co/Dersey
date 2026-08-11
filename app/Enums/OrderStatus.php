<?php

namespace App\Enums;

use App\Enums\Contracts\HasEnumOption;

enum OrderStatus: string implements HasEnumOption
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return __('enums.order_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Confirmed, self::Processing, self::Shipped, self::OutForDelivery => 'accent',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Returned => 'warning',
        };
    }

    /**
     * The only source of truth for which order-status changes are legal.
     * Deliberately a fixed map, not "anything goes forward" — shipped/
     * out_for_delivery can't jump back to cancelled once the package has
     * physically left (a failed/refused delivery becomes "returned"
     * instead), and nothing can ever re-enter pending once left.
     */
    private function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::OutForDelivery, self::Returned],
            self::OutForDelivery => [self::Delivered, self::Returned],
            self::Delivered => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
