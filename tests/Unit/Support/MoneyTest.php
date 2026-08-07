<?php

use App\Support\Money;

it('adds two money values', function () {
    $result = Money::fromMinor(1000)->add(Money::fromMinor(500));

    expect($result->minor())->toBe(1500);
});

it('subtracts money values', function () {
    $result = Money::fromMinor(1000)->subtract(Money::fromMinor(300));

    expect($result->minor())->toBe(700);
});

it('throws when subtraction would go negative', function () {
    Money::fromMinor(100)->subtract(Money::fromMinor(200));
})->throws(InvalidArgumentException::class);

it('multiplies by a quantity', function () {
    $result = Money::fromMinor(2500)->multiply(3);

    expect($result->minor())->toBe(7500);
});

it('calculates a percentage discount', function () {
    $result = Money::fromMinor(10000)->percentage(15);

    expect($result->minor())->toBe(1500);
});

it('rounds percentage calculations half away from zero', function () {
    // 333 * 50% = 166.5 -> rounds to 167
    $result = Money::fromMinor(333)->percentage(50);

    expect($result->minor())->toBe(167);
});

it('builds money from a major decimal string without float precision loss', function () {
    expect(Money::fromMajor('249.99')->minor())->toBe(24999)
        ->and(Money::fromMajor('249')->minor())->toBe(24900)
        ->and(Money::fromMajor('249.5')->minor())->toBe(24950);
});

it('rejects invalid major amount strings', function () {
    Money::fromMajor('abc');
})->throws(InvalidArgumentException::class);

it('rejects negative amounts', function () {
    Money::fromMinor(-100);
})->throws(InvalidArgumentException::class);

it('formats money for arabic and english locales', function () {
    $money = Money::fromMinor(24999);

    expect($money->format('ar'))->toBe('249.99 ج.م')
        ->and($money->format('en'))->toBe('EGP 249.99');
});

it('is immutable across operations', function () {
    $original = Money::fromMinor(1000);
    $original->add(Money::fromMinor(500));
    $original->percentage(50);

    expect($original->minor())->toBe(1000);
});
