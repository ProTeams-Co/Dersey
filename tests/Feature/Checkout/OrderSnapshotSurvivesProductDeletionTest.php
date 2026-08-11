<?php

use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A variant that's actually gone through InventoryService::commit() (the
 * real checkout path below) always leaves an inventory_movements row
 * behind, and that table's variant_id is restrictOnDelete() (approved in
 * Batch 2.3 to protect the audit trail) - so the *checked-out* item's
 * product/variant can never be force-deleted afterward, by design. This
 * test therefore does two things, matching the same approved pattern
 * OrderSeeder uses for its own "deleted product" orders:
 *   1. Runs a real checkout through createFromCart() - proving
 *      snapshotItem() actually captures name/sku/price correctly at
 *      order time (the logic this test exists to cover).
 *   2. Adds one more item directly for a variant that was never
 *      committed, then force-deletes *that* product - proving
 *      order_items' nullOnDelete FKs and snapshot columns behave
 *      correctly once a real deletion does go through.
 */
it('keeps the order item snapshot intact after the product it was bought from is deleted', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    $zone = ShippingZone::factory()->create();
    $address->governorate->update(['shipping_zone_id' => $zone->id]);
    $shippingMethod = ShippingMethod::factory()->create(['zone_id' => $zone->id]);

    $orderedVariant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $cartService = app(CartService::class);
    $orderService = app(OrderService::class);

    $cart = $cartService->findOrCreateForUser($user);
    $cartService->add($cart, $orderedVariant, 1);

    $order = $orderService->createFromCart(
        cart: $cart->fresh(),
        shippingAddress: $address,
        shippingMethod: $shippingMethod,
        paymentMethod: PaymentMethod::CashOnDelivery,
        user: $user,
    );

    // The item that went through the real checkout - confirms
    // snapshotItem() captured the right data at order time.
    $checkedOutItem = $order->items()->where('variant_id', $orderedVariant->id)->first();
    expect($checkedOutItem->product_name['en'])->toBe($orderedVariant->product->translate('en')->name)
        ->and($checkedOutItem->sku)->toBe($orderedVariant->sku);

    // A second, never-committed variant added directly - this is the one
    // whose product we can actually hard-delete.
    $deletableVariant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $deletableVariant->load('product.translations');

    $order->items()->create([
        'product_id' => $deletableVariant->product_id,
        'variant_id' => $deletableVariant->id,
        'product_name' => $deletableVariant->product->translations
            ->mapWithKeys(fn ($t) => [$t->locale => $t->name])->all(),
        'variant_options' => null,
        'sku' => $deletableVariant->sku,
        'image_path' => null,
        'unit_price' => $deletableVariant->final_price,
        'quantity' => 1,
        'line_total' => $deletableVariant->final_price,
    ]);

    $deletableItem = $order->items()->where('variant_id', $deletableVariant->id)->first();
    $originalProductName = $deletableItem->product_name;
    $originalSku = $deletableItem->sku;
    $originalUnitPrice = $deletableItem->unit_price;

    // Hard delete, not soft delete - order_items.product_id/variant_id
    // are nullOnDelete specifically to survive this.
    Product::find($deletableVariant->product_id)->forceDelete();

    $deletableItem->refresh();

    expect($deletableItem->product_id)->toBeNull()
        ->and($deletableItem->variant_id)->toBeNull()
        ->and($deletableItem->product_name)->toBe($originalProductName)
        ->and($deletableItem->sku)->toBe($originalSku)
        ->and($deletableItem->unit_price->equals($originalUnitPrice))->toBeTrue()
        ->and($deletableItem->line_total)->not->toBeNull();
});
