<?php

use App\Enums\InventoryMovementType;
use App\Exceptions\StocktakeNoChangeException;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantMatrixService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderService;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeAdjustableVariant(int $stock = 20, int $threshold = 5): ProductVariant
{
    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock_quantity' => $stock,
        'reserved_quantity' => 0,
        'low_stock_threshold' => $threshold,
    ]);
}

// ---- Mandatory test 13: threshold edit does NOT go through InventoryService ----

it('updates low_stock_threshold without recording any inventory movement', function () {
    $admin = actingAdminWithRole();
    $variant = makeAdjustableVariant();

    $response = $this->put(route('admin.inventory.threshold', $variant->id), [
        'low_stock_threshold' => 15,
    ]);

    $response->assertRedirect();
    expect($variant->fresh()->low_stock_threshold)->toBe(15);
    expect(InventoryMovement::where('variant_id', $variant->id)->count())->toBe(0);
});

// ---- Mandatory tests 14/15: manual In/Out record the right movement type ----

it('records an In movement with the correct quantity for a manual increase', function () {
    $admin = actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $response = $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'in',
        'quantity' => 10,
        'note' => 'Stock received from supplier.',
    ]);

    $response->assertRedirect();
    expect($variant->fresh()->stock_quantity)->toBe(30);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::In);
    expect($movement->quantity)->toBe(10);
});

it('records an Out movement with the correct (negative) quantity for a manual decrease', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $response = $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'out',
        'quantity' => 8,
        'note' => 'Damaged units removed.',
    ]);

    $response->assertRedirect();
    expect($variant->fresh()->stock_quantity)->toBe(12);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::Out);
    expect($movement->quantity)->toBe(-8);
});

// ---- Mandatory test 16: negative result -> 422, stock unchanged ----

it('rejects a manual decrease that would push stock below zero, with a 422, and leaves stock unchanged', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 5);

    $response = $this->postJson(route('admin.inventory.adjust', $variant->id), [
        'type' => 'out',
        'quantity' => 10,
        'note' => 'Too many.',
    ]);

    $response->assertStatus(422);
    expect($variant->fresh()->stock_quantity)->toBe(5);
    expect(InventoryMovement::where('variant_id', $variant->id)->count())->toBe(0);
});

// ---- Mandatory test 17: empty note -> 422 ----

it('rejects an empty note with a 422, for every adjustment type', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $inResponse = $this->postJson(route('admin.inventory.adjust', $variant->id), ['type' => 'in', 'quantity' => 5, 'note' => '']);
    $inResponse->assertStatus(422);
    $inResponse->assertJsonValidationErrors('note');

    $outResponse = $this->postJson(route('admin.inventory.adjust', $variant->id), ['type' => 'out', 'quantity' => 5, 'note' => '']);
    $outResponse->assertStatus(422);

    $adjustResponse = $this->postJson(route('admin.inventory.adjust', $variant->id), ['type' => 'adjust', 'new_count' => 30, 'note' => '']);
    $adjustResponse->assertStatus(422);

    expect(InventoryMovement::where('variant_id', $variant->id)->count())->toBe(0);
});

// ---- Mandatory test 18: admin_id recorded correctly ----

it('records the acting admin\'s id on a manual adjustment', function () {
    $admin = actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'in',
        'quantity' => 5,
        'note' => 'Restock.',
    ]);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->admin_id)->toBe($admin->id);
});

// ---- Mandatory test 19: quantity_before/quantity_after correct ----

it('records the correct quantity_before/quantity_after on a manual adjustment', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'in',
        'quantity' => 7,
        'note' => 'Restock.',
    ]);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->quantity_before)->toBe(20);
    expect($movement->quantity_after)->toBe(27);
});

// ---- Decision 1's own 4 mandatory tests ----

it('leaves admin_id null when InventoryService::adjust() is called with no admin (old behavior intact)', function () {
    $variant = makeAdjustableVariant(stock: 10);

    $movement = app(InventoryService::class)->adjust($variant, 5, InventoryMovementType::In);

    expect($movement->admin_id)->toBeNull();
});

it('records admin_id when InventoryService::adjust() is called with an admin', function () {
    $admin = Admin::factory()->create();
    $variant = makeAdjustableVariant(stock: 10);

    $movement = app(InventoryService::class)->adjust($variant, 5, InventoryMovementType::In, null, null, $admin);

    expect($movement->admin_id)->toBe($admin->id);
});

it('leaves checkout-driven sale movements with admin_id = null - the checkout flow is unaffected', function () {
    $user = \App\Models\User::factory()->create();
    $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
    $zone = \App\Models\ShippingZone::factory()->create();
    $address->governorate->update(['shipping_zone_id' => $zone->id]);
    $shippingMethod = \App\Models\ShippingMethod::factory()->create(['zone_id' => $zone->id]);

    $variant = makeAdjustableVariant(stock: 10);

    $cartService = app(CartService::class);
    $orderService = app(OrderService::class);

    $cart = $cartService->findOrCreateForUser($user);
    $cartService->add($cart, $variant, 2);

    $order = $orderService->createFromCart(
        cart: $cart->fresh(),
        shippingAddress: $address,
        shippingMethod: $shippingMethod,
        paymentMethod: \App\Enums\PaymentMethod::CashOnDelivery,
        user: $user,
    );

    $movement = InventoryMovement::where('variant_id', $variant->id)
        ->where('type', InventoryMovementType::Out)
        ->latest('id')
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->admin_id)->toBeNull();
});

it('records the acting admin on the initial-stock movement written from ProductVariantMatrixService', function () {
    $admin = actingAdminWithRole();

    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);
    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $variant = app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id]])->first();

    app(ProductVariantMatrixService::class)->updateVariants($product, [[
        'id' => $variant->id,
        'version' => $variant->version,
        'sku' => $variant->sku,
        'price' => null,
        'compare_at_price' => null,
        'is_active' => true,
        'initial_stock' => 25,
    ]]);

    $movement = InventoryMovement::where('variant_id', $variant->id)->where('type', InventoryMovementType::In)->first();
    expect($movement)->not->toBeNull();
    expect($movement->admin_id)->toBe($admin->id);
});

// ---- Decision 2's own 3 mandatory stocktake tests ----

it('records a positive Adjust movement when the stocktake count is higher than current stock', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $response = $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'adjust',
        'new_count' => 35,
        'note' => 'Physical stocktake.',
    ]);

    $response->assertRedirect();
    expect($variant->fresh()->stock_quantity)->toBe(35);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::Adjust);
    expect($movement->quantity)->toBe(15);
    expect($movement->quantity_before)->toBe(20);
    expect($movement->quantity_after)->toBe(35);
});

it('records a negative Adjust movement when the stocktake count is lower than current stock', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $response = $this->post(route('admin.inventory.adjust', $variant->id), [
        'type' => 'adjust',
        'new_count' => 12,
        'note' => 'Physical stocktake found fewer units.',
    ]);

    $response->assertRedirect();
    expect($variant->fresh()->stock_quantity)->toBe(12);

    $movement = InventoryMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::Adjust);
    expect($movement->quantity)->toBe(-8);
});

it('rejects a stocktake whose count exactly matches current stock, with a 422, and records nothing', function () {
    actingAdminWithRole();
    $variant = makeAdjustableVariant(stock: 20);

    $response = $this->postJson(route('admin.inventory.adjust', $variant->id), [
        'type' => 'adjust',
        'new_count' => 20,
        'note' => 'No change found.',
    ]);

    $response->assertStatus(422);
    expect($variant->fresh()->stock_quantity)->toBe(20);
    expect(InventoryMovement::where('variant_id', $variant->id)->count())->toBe(0);
});

it('computes the stocktake delta AFTER acquiring the row lock, via InventoryService::stocktake() directly', function () {
    $variant = makeAdjustableVariant(stock: 20);

    $movement = app(InventoryService::class)->stocktake($variant, 50, 'Direct service call.');

    expect($movement->quantity)->toBe(30);
    expect($movement->quantity_before)->toBe(20);
    expect($movement->quantity_after)->toBe(50);

    expect(fn () => app(InventoryService::class)->stocktake($variant->fresh(), 50, 'Same count.'))
        ->toThrow(StocktakeNoChangeException::class);
});
