<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;

final class Money implements JsonSerializable
{
    private function __construct(private readonly int $minorAmount)
    {
        if ($minorAmount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    // تستقبل المبلغ بالوحدة الصغرى (Minor Unit).
    public static function fromMinor(int $minor): self
    {
        return new self($minor);
    }

    // بتحول المبلغ المكتوب بالجنيه إلى قروش.
    public static function fromMajor(string $major): self
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $major)) {
            throw new InvalidArgumentException("Invalid money value: {$major}");
        }

        [$integerPart, $decimalPart] = array_pad(explode('.', $major, 2), 2, '0');
        $decimalPart = str_pad(substr($decimalPart, 0, 2), 2, '0');

        return new self(((int) $integerPart) * 100 + (int) $decimalPart);
    }

    /**
     * The null-safe form of fromMajor() - the one actually needed at every
     * form-input boundary (ProductsController, ProductVariantMatrixService),
     * where an optional money field (compare_at_price, cost_price, ...)
     * legitimately arrives as null (already-nullable validation, or
     * ConvertEmptyStringsToNull turning a blank field into null before
     * validation even runs) alongside a required one that never is.
     * Centralized here instead of each caller repeating its own
     * `$value === null ? null : self::fromMajor($value)` ternary.
     */
    public static function fromMajorNullable(?string $major): ?self
    {
        return $major === null ? null : self::fromMajor($major);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->minorAmount + $other->minorAmount);
    }

    public function subtract(self $other): self
    {
        return new self($this->minorAmount - $other->minorAmount);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self($this->minorAmount * $quantity);
    }

    /**
     * Rounding for the whole Money class happens here, and only here.
     */
    public function percentage(float $rate): self
    {
        return new self(intval(round($this->minorAmount * $rate / 100)));
    }

    public function minor(): int
    {
        return $this->minorAmount;
    }

    public function format(string $locale = 'ar'): string
    {
        $integerPart = intdiv($this->minorAmount, 100);
        $fractionPart = str_pad((string) ($this->minorAmount % 100), 2, '0', STR_PAD_LEFT);
        $amount = "{$integerPart}.{$fractionPart}";

        return $locale === 'ar' ? "{$amount} ج.م" : "EGP {$amount}";
    }

    public function equals(self $other): bool
    {
        return $this->minorAmount === $other->minorAmount;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    public function jsonSerialize(): int
    {
        return $this->minorAmount;
    }
}
