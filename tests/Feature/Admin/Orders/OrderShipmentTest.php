<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('adds a shipment with the cost stored in piasters', function () {
    actingAdminWithRole();
    $order = Order::factory()->create();

    $this->post(route('admin.orders.shipments.store', $order->id), [
        'carrier' => 'Bosta',
        'tracking_number' => 'TRK-123',
        'cost' => '45.50',
    ])->assertRedirect();

    $shipment = $order->shipments()->first();
    expect($shipment->carrier)->toBe('Bosta');

    $raw = DB::table('shipments')->where('id', $shipment->id)->value('cost');
    expect($raw)->toBe(4550);
});

it('does not change the order status when a shipment is added', function () {
    actingAdminWithRole();
    $order = Order::factory()->withStatus(OrderStatus::Processing)->create();

    $this->post(route('admin.orders.shipments.store', $order->id), [
        'carrier' => 'Bosta',
        'cost' => '30.00',
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('updates an existing shipment', function () {
    actingAdminWithRole();
    $order = Order::factory()->create();
    $order->shipments()->create(['carrier' => 'Old Carrier', 'cost' => 3000]);
    $shipment = $order->shipments()->first();

    $this->put(route('admin.orders.shipments.update', [$order->id, $shipment->id]), [
        'carrier' => 'New Carrier',
        'cost' => '55.00',
    ])->assertRedirect();

    expect($shipment->fresh()->carrier)->toBe('New Carrier');
});

it('rejects a malformed shipment cost with a 422', function () {
    actingAdminWithRole();
    $order = Order::factory()->create();

    $response = $this->postJson(route('admin.orders.shipments.store', $order->id), [
        'carrier' => 'Bosta',
        'cost' => 'not-a-number',
    ]);

    $response->assertStatus(422);
    expect($order->shipments()->count())->toBe(0);
});
