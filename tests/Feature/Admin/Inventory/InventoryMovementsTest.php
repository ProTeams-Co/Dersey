<?php

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeMovementVariant(): ProductVariant
{
    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);

    return ProductVariant::factory()->create(['product_id' => $product->id]);
}

it('renders the movement log for an authorized admin, newest first', function () {
    actingAdminWithRole();
    $variant = makeMovementVariant();

    $older = InventoryMovement::factory()->for($variant, 'variant')->create(['created_at' => now()->subDays(2)]);
    $newer = InventoryMovement::factory()->for($variant, 'variant')->create(['created_at' => now()->subMinute()]);

    $response = $this->get(route('admin.inventory.movements.index'), ['Accept' => 'application/json']);
    $response->assertOk();

    $ids = collect($response->json('rows'))->pluck('id');
    expect($ids->search($newer->id))->toBeLessThan($ids->search($older->id));
});

it('denies a non-permitted admin from viewing the movement log', function () {
    actingAdminWithRole('support');
    $this->get(route('admin.inventory.movements.index'))->assertForbidden();
});

it('filters the movement log by type, admin, and variant', function () {
    actingAdminWithRole();
    $admin = actingAdminWithRole(); // second admin, distinct id
    $variant = makeMovementVariant();
    $otherVariant = makeMovementVariant();

    $matching = InventoryMovement::factory()->for($variant, 'variant')->for($admin, 'admin')->create(['type' => InventoryMovementType::In]);
    InventoryMovement::factory()->for($otherVariant, 'variant')->create(['type' => InventoryMovementType::Out]);

    $byType = $this->get(route('admin.inventory.movements.index', ['filter' => ['type' => 'in']]), ['Accept' => 'application/json']);
    expect(collect($byType->json('rows'))->pluck('id'))->toContain($matching->id);

    $byAdmin = $this->get(route('admin.inventory.movements.index', ['filter' => ['admin_id' => $admin->id]]), ['Accept' => 'application/json']);
    expect(collect($byAdmin->json('rows'))->pluck('id'))->toContain($matching->id);

    $byVariant = $this->get(route('admin.inventory.movements.index', ['filter' => ['variant_id' => $variant->id]]), ['Accept' => 'application/json']);
    $byVariantIds = collect($byVariant->json('rows'))->pluck('id');
    expect($byVariantIds)->toContain($matching->id);
    foreach (InventoryMovement::where('variant_id', $otherVariant->id)->pluck('id') as $unrelatedId) {
        expect($byVariantIds)->not->toContain($unrelatedId);
    }
});

it('refuses to update an inventory movement - no edit route exists at all', function () {
    actingAdminWithRole();
    $variant = makeMovementVariant();
    $movement = InventoryMovement::factory()->for($variant, 'variant')->create();

    expect(fn () => route('admin.inventory.movements.update', $movement->id))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);

    // Belt and suspenders: even a raw PUT to the show/index-style URL
    // 404s or 405s - never a 200.
    $response = $this->put('/admin/inventory/movements/'.$movement->id, ['note' => 'tampered']);
    expect($response->status())->toBeIn([404, 405]);
    expect($movement->fresh()->note)->toBe($movement->note);
});

it('refuses to delete an inventory movement - no delete route exists at all', function () {
    actingAdminWithRole();
    $variant = makeMovementVariant();
    $movement = InventoryMovement::factory()->for($variant, 'variant')->create();

    expect(fn () => route('admin.inventory.movements.destroy', $movement->id))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);

    $response = $this->delete('/admin/inventory/movements/'.$movement->id);
    expect($response->status())->toBeIn([404, 405]);
    expect(InventoryMovement::find($movement->id))->not->toBeNull();
});

it('loads the movement log with 50 movements in a fixed query count - no N+1', function () {
    actingAdminWithRole();
    $variant = makeMovementVariant();

    InventoryMovement::factory()->for($variant, 'variant')->count(5)->create();

    $this->get(route('admin.inventory.movements.index'))->assertOk();
    DB::enableQueryLog();
    $this->get(route('admin.inventory.movements.index'))->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    InventoryMovement::factory()->for($variant, 'variant')->count(45)->create();
    expect(InventoryMovement::count())->toBe(50);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.inventory.movements.index'))->assertOk();
    $queriesForFifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFifty)->toBe($queriesForFive);
});
