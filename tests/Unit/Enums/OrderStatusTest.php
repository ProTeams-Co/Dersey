<?php

use App\Enums\OrderStatus;

it('allows the normal happy-path transitions', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed))->toBeTrue();
    expect(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Processing))->toBeTrue();
    expect(OrderStatus::Processing->canTransitionTo(OrderStatus::Shipped))->toBeTrue();
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::OutForDelivery))->toBeTrue();
    expect(OrderStatus::OutForDelivery->canTransitionTo(OrderStatus::Delivered))->toBeTrue();
});

it('blocks a delivered order from going back to pending', function () {
    // The flagship case this method exists to prevent.
    expect(OrderStatus::Delivered->canTransitionTo(OrderStatus::Pending))->toBeFalse();
});

it('blocks every other backward or skipped transition', function () {
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Pending))->toBeFalse();
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Shipped))->toBeFalse();
    expect(OrderStatus::Delivered->canTransitionTo(OrderStatus::Confirmed))->toBeFalse();
    expect(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Pending))->toBeFalse();
});

it('does not allow cancelling an order once it has shipped', function () {
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
    expect(OrderStatus::OutForDelivery->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
});

it('allows a failed/refused delivery to become returned', function () {
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Returned))->toBeTrue();
    expect(OrderStatus::OutForDelivery->canTransitionTo(OrderStatus::Returned))->toBeTrue();
    expect(OrderStatus::Delivered->canTransitionTo(OrderStatus::Returned))->toBeTrue();
});

it('treats cancelled and returned as final, everything else as not final', function () {
    expect(OrderStatus::Cancelled->isFinal())->toBeTrue();
    expect(OrderStatus::Returned->isFinal())->toBeTrue();

    expect(OrderStatus::Pending->isFinal())->toBeFalse();
    expect(OrderStatus::Delivered->isFinal())->toBeFalse();
});
