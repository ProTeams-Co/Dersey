<?php

use App\Enums\OrderStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies a valid transition and records it in the status history', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Pending)->create();

    $this->post(route('admin.orders.transition', $order->id), [
        'status' => 'confirmed',
        'comment' => 'Confirmed by phone.',
    ])->assertRedirect();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Confirmed);

    $history = $order->statusHistories()->latest('id')->first();
    expect($history->from_status)->toBe(OrderStatus::Pending)
        ->and($history->to_status)->toBe(OrderStatus::Confirmed)
        ->and($history->comment)->toBe('Confirmed by phone.');
});

it('rejects an invalid transition with a 422, not a 500', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Pending)->create();

    $response = $this->postJson(route('admin.orders.transition', $order->id), [
        'status' => 'shipped',
        'comment' => 'skip ahead',
    ]);

    $response->assertStatus(422);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('rejects any transition attempted from a final status, with a 422', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Cancelled)->create();

    $response = $this->postJson(route('admin.orders.transition', $order->id), [
        'status' => 'confirmed',
        'comment' => 'try to revive it',
    ]);

    $response->assertStatus(422);
});

it('rejects an empty comment with a 422', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Pending)->create();

    $response = $this->postJson(route('admin.orders.transition', $order->id), [
        'status' => 'confirmed',
        'comment' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('comment');
});

it('restores inventory exactly once when an order is cancelled - not twice on a repeated attempt', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Confirmed)->create();
    $item = OrderItem::factory()->for($order)->create(['quantity' => 3]);
    $stockBefore = $item->variant->stock_quantity;

    $this->post(route('admin.orders.transition', $order->id), [
        'status' => 'cancelled',
        'comment' => 'Customer changed their mind.',
    ])->assertRedirect();

    expect($item->variant->fresh()->stock_quantity)->toBe($stockBefore + 3);
    expect(InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->count())->toBe(1);

    // A second cancel attempt on an already-cancelled order must be
    // rejected by the state machine itself (Cancelled is final) - proving
    // restoreInventory() structurally cannot run twice through this path.
    $second = $this->postJson(route('admin.orders.transition', $order->id), [
        'status' => 'cancelled',
        'comment' => 'try again',
    ]);

    $second->assertStatus(422);
    expect($item->variant->fresh()->stock_quantity)->toBe($stockBefore + 3);
    expect(InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->count())->toBe(1);
});

it('records the acting admin on the inventory movement when an admin cancels an order (Batch 3.3 gap fix)', function () {
    $admin = actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Confirmed)->create();
    OrderItem::factory()->for($order)->create();

    $this->post(route('admin.orders.transition', $order->id), [
        'status' => 'cancelled',
        'comment' => 'Out of stock at supplier.',
    ])->assertRedirect();

    $movement = InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->first();
    $history = $order->statusHistories()->latest('id')->first();

    expect($movement->admin_id)->toBe($admin->id)
        ->and($history->changed_by_type)->toBe($admin->getMorphClass())
        ->and($history->changed_by_id)->toBe($admin->id);
});

it('leaves admin_id null when a User cancels an order, while the status history still records the User', function () {
    $order = Order::factory()->withStatus(OrderStatus::Confirmed)->create();
    OrderItem::factory()->for($order)->create();
    $user = User::factory()->create();

    app(OrderService::class)->transitionTo($order, OrderStatus::Cancelled, 'Customer requested cancellation.', $user);

    $movement = InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->first();
    $history = $order->statusHistories()->latest('id')->first();

    expect($movement->admin_id)->toBeNull()
        ->and($history->changed_by_type)->toBe($user->getMorphClass())
        ->and($history->changed_by_id)->toBe($user->id);
});

it('leaves both admin_id and changed_by null for a system-initiated cancellation (no $changedBy at all)', function () {
    $order = Order::factory()->withStatus(OrderStatus::Confirmed)->create();
    OrderItem::factory()->for($order)->create();

    app(OrderService::class)->transitionTo($order, OrderStatus::Cancelled, 'Automated stock cleanup.');

    $movement = InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->first();
    $history = $order->statusHistories()->latest('id')->first();

    expect($movement->admin_id)->toBeNull()
        ->and($history->changed_by_type)->toBeNull()
        ->and($history->changed_by_id)->toBeNull();
});

it('does not restore inventory when an order is marked as returned (Batch 3.4 decision 2, intentional)', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Delivered)->create();
    $item = OrderItem::factory()->for($order)->create(['quantity' => 2]);
    $stockBefore = $item->variant->stock_quantity;

    $this->post(route('admin.orders.transition', $order->id), [
        'status' => 'returned',
        'comment' => 'Customer returned the item.',
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Returned);
    expect($item->variant->fresh()->stock_quantity)->toBe($stockBefore);
    expect(InventoryMovement::where('reference_type', Order::class)->where('reference_id', $order->id)->count())->toBe(0);
});
