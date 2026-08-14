<?php

use App\Casts\MoneyCast;
use App\Models\Product;
use App\Support\Money;

/**
 * Batch 3.2-M - MoneyCast::set() went from silently (int)-truncating any
 * non-Money input to rejecting anything ambiguous outright. Product is
 * used as the throwaway $model argument set()/get() require - any
 * MoneyCast-using model would do, this one is simply already at hand.
 */
function moneyCast(): MoneyCast
{
    return new MoneyCast;
}

it('set() converts a Money instance to its minor value', function () {
    expect(moneyCast()->set(new Product, 'base_price', Money::fromMinor(19950), []))->toBe(19950);
});

it('set() passes a raw int straight through - always piasters, matching every factory in the project', function () {
    expect(moneyCast()->set(new Product, 'base_price', 19950, []))->toBe(19950);
});

it('set() converts null to null', function () {
    expect(moneyCast()->set(new Product, 'compare_at_price', null, []))->toBeNull();
});

it('set() rejects a decimal major-unit string with an exception naming the column, value, and fix', function () {
    try {
        moneyCast()->set(new Product, 'base_price', '199.50', []);
        expect(false)->toBeTrue('Expected InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())
            ->toContain('base_price')
            ->toContain('199.50')
            ->toContain('Money::fromMajor');
    }
});

it('set() rejects an integer-looking string with an exception - no implicit "is this piasters or pounds" guess', function () {
    expect(fn () => moneyCast()->set(new Product, 'base_price', '199', []))
        ->toThrow(InvalidArgumentException::class);
});

it('set() rejects a float outright - lossy and ambiguous', function () {
    expect(fn () => moneyCast()->set(new Product, 'base_price', 199.50, []))
        ->toThrow(InvalidArgumentException::class);
});

it('get() is unchanged - still converts a raw minor int to Money', function () {
    $result = moneyCast()->get(new Product, 'base_price', 19950, []);

    expect($result)->toBeInstanceOf(Money::class)
        ->and($result->minor())->toBe(19950);
});

it('get() is unchanged - still converts null to null', function () {
    expect(moneyCast()->get(new Product, 'compare_at_price', null, []))->toBeNull();
});
