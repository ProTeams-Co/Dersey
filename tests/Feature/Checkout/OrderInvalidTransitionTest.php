<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to transition an order from delivered back to pending', function () {
    $order = Order::factory()->withStatus(OrderStatus::Delivered)->create();

    $orderService = app(OrderService::class);

    expect(fn () => $orderService->transitionTo($order, OrderStatus::Pending))
        ->toThrow(InvalidOrderTransitionException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->statusHistories()->count())->toBe(0);
});

it('refuses every other backward or skipped transition, matching OrderStatus::canTransitionTo()', function () {
    $order = Order::factory()->withStatus(OrderStatus::Shipped)->create();
    $orderService = app(OrderService::class);

    // Shipped can only go to OutForDelivery or Returned - not back to
    // Pending/Confirmed/Processing, and not straight to Delivered.
    expect(fn () => $orderService->transitionTo($order, OrderStatus::Confirmed))
        ->toThrow(InvalidOrderTransitionException::class);
    expect(fn () => $orderService->transitionTo($order, OrderStatus::Delivered))
        ->toThrow(InvalidOrderTransitionException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});
