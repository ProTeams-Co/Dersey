<?php

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks a review as a verified purchase only when order_item_id belongs to a delivered order', function () {
    $deliveredOrder = Order::factory()->withStatus(OrderStatus::Delivered)->create();
    $deliveredItem = OrderItem::factory()->create(['order_id' => $deliveredOrder->id]);

    $pendingOrder = Order::factory()->withStatus(OrderStatus::Pending)->create();
    $pendingItem = OrderItem::factory()->create(['order_id' => $pendingOrder->id]);

    $verified = Review::create([
        'product_id' => $deliveredItem->product_id,
        'user_id' => $deliveredOrder->user_id,
        'order_item_id' => $deliveredItem->id,
        'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    $unverifiedByOrder = Review::create([
        'product_id' => $pendingItem->product_id,
        'user_id' => $pendingOrder->user_id,
        'order_item_id' => $pendingItem->id,
        'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    $unverifiedNoItem = Review::create([
        'product_id' => $deliveredItem->product_id,
        'user_id' => $pendingOrder->user_id,
        'order_item_id' => null,
        'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    expect($verified->fresh()->is_verified_purchase)->toBeTrue()
        ->and($unverifiedByOrder->fresh()->is_verified_purchase)->toBeFalse()
        ->and($unverifiedNoItem->fresh()->is_verified_purchase)->toBeFalse();
});
