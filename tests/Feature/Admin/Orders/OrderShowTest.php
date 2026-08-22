<?php

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Batch 3.4-fix - the N+1 test below used to populate only order_items,
 * leaving status history/changedBy/shipments/payments/inventory movements
 * completely untested for N+1 (a real gap: any of those eager-load
 * relations could regress into a per-row query and this test would still
 * pass, since none of them ever varied). This builds every relation the
 * show page actually touches: 5 status history rows mixing Admin,
 * User, and no changed_by at all (so the polymorphic changedBy eager
 * load is exercised for all three shapes at once), one shipment, one
 * payment, and two inventory movements against two different variants
 * (one attributed to an admin, one not).
 */
function fullyPopulateOrder(Order $order, int $itemCount): void
{
    OrderItem::factory()->for($order)->count($itemCount)->create();

    $admin = Admin::factory()->create();
    $user = User::factory()->create();

    $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::Pending]);
    $order->statusHistories()->create(['from_status' => OrderStatus::Pending, 'to_status' => OrderStatus::Confirmed, 'changed_by_type' => $admin->getMorphClass(), 'changed_by_id' => $admin->id]);
    $order->statusHistories()->create(['from_status' => OrderStatus::Confirmed, 'to_status' => OrderStatus::Processing, 'changed_by_type' => $user->getMorphClass(), 'changed_by_id' => $user->id]);
    $order->statusHistories()->create(['from_status' => OrderStatus::Processing, 'to_status' => OrderStatus::Shipped, 'changed_by_type' => $admin->getMorphClass(), 'changed_by_id' => $admin->id]);
    $order->statusHistories()->create(['from_status' => OrderStatus::Shipped, 'to_status' => OrderStatus::OutForDelivery]);

    $order->shipments()->create([
        'carrier' => 'Aramex',
        'tracking_number' => 'TRK123',
        'tracking_url' => 'https://example.com/t',
        'cost' => 5000,
        'shipped_at' => now(),
    ]);

    $order->payments()->create([
        'gateway' => 'paymob',
        'method' => 'card',
        'amount' => 10000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $variant = ProductVariant::factory()->create();
    $variant->movements()->create([
        'type' => InventoryMovementType::Out,
        'quantity' => -2,
        'quantity_before' => 10,
        'quantity_after' => 8,
        'reference_type' => $order->getMorphClass(),
        'reference_id' => $order->id,
        'admin_id' => $admin->id,
    ]);

    $otherVariant = ProductVariant::factory()->create();
    $otherVariant->movements()->create([
        'type' => InventoryMovementType::In,
        'quantity' => 2,
        'quantity_before' => 8,
        'quantity_after' => 10,
        'reference_type' => $order->getMorphClass(),
        'reference_id' => $order->id,
        'admin_id' => null,
    ]);
}

it('renders the order details page and shows the snapshot data, not the live catalog', function () {
    actingAdminWithRole();
    $order = Order::factory()->create();
    $item = OrderItem::factory()->for($order)->create([
        'product_name' => ['ar' => 'اسم وقت الطلب', 'en' => 'Name at order time'],
        'sku' => 'SNAPSHOT-SKU',
    ]);

    // The catalog product is renamed AFTER the order - the page must
    // still show the snapshot, not the live (renamed) product.
    $item->product?->translate('ar')?->update(['name' => 'اسم جديد بعد الطلب']);

    $response = $this->get(route('admin.orders.show', $order->id));

    $response->assertOk();
    $response->assertSee('اسم وقت الطلب');
    $response->assertSee('SNAPSHOT-SKU', false);
    $response->assertDontSee('اسم جديد بعد الطلب');
});

it('loads the order details page with a fixed query count regardless of item count - no N+1', function () {
    actingAdminWithRole();

    // Both orders get the FULL relation set (status history mixing
    // Admin/User/system, a shipment, a payment, two inventory movements) -
    // only order_items varies (2 vs 20). If any relation regressed into a
    // per-row query, it would show up here since every relation the show
    // page touches is actually populated on both sides of the comparison.
    $fewOrder = Order::factory()->create();
    fullyPopulateOrder($fewOrder, 2);
    $this->get(route('admin.orders.show', $fewOrder->id));
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.orders.show', $fewOrder->id));
    $few = count(DB::getQueryLog());
    DB::disableQueryLog();

    $manyOrder = Order::factory()->create();
    fullyPopulateOrder($manyOrder, 20);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.orders.show', $manyOrder->id));
    $many = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($few)->toBe($many)->and($few)->toBe(11);
});

it('saves the admin note', function () {
    actingAdminWithRole();
    $order = Order::factory()->create(['admin_note' => null]);

    $this->patch(route('admin.orders.note', $order->id), [
        'admin_note' => 'Called the customer to confirm the address.',
    ])->assertRedirect();

    expect($order->fresh()->admin_note)->toBe('Called the customer to confirm the address.');
});

it('rejects an attempt to modify order_items through the note endpoint - it is silently ignored', function () {
    actingAdminWithRole();
    $order = Order::factory()->create();
    $item = OrderItem::factory()->for($order)->create(['sku' => 'ORIGINAL-SKU', 'quantity' => 1]);

    $this->patch(route('admin.orders.note', $order->id), [
        'admin_note' => 'note',
        'items' => [['id' => $item->id, 'sku' => 'HACKED-SKU', 'quantity' => 999]],
    ])->assertRedirect();

    expect($item->fresh()->sku)->toBe('ORIGINAL-SKU')
        ->and($item->fresh()->quantity)->toBe(1);
});

it('ignores an attempt to modify payment_status through this screen', function () {
    actingAdminWithRole();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid]);

    $this->patch(route('admin.orders.note', $order->id), [
        'admin_note' => 'note',
        'payment_status' => 'paid',
    ])->assertRedirect();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});
