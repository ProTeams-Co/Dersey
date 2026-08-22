<?php

use App\Enums\AdminStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('renders the orders list for an authorized admin', function () {
    actingAdminWithRole();
    Order::factory()->count(3)->create();

    $this->get(route('admin.orders.index'))->assertOk();
});

/**
 * Every seeded role (super-admin, admin, manager, support) already has
 * orders.view (support's own role explicitly includes it) - there is no
 * pre-seeded role to test denial against, unlike every other Batch 3.x
 * "denied" test (which reuses 'support' because it genuinely lacks that
 * OTHER resource's permission). A fresh, deliberately empty role is the
 * only way to actually exercise the denial path for orders specifically.
 */
it('denies a non-permitted admin from viewing the orders list', function () {
    test()->seed(RolePermissionSeeder::class);
    $role = Role::create(['name' => 'no-orders-access', 'guard_name' => 'admin']);
    $admin = Admin::factory()->create(['status' => AdminStatus::Active]);
    $admin->assignRole($role);
    test()->actingAs($admin, 'admin');

    $this->get(route('admin.orders.index'))->assertForbidden();
});

it('filters by status', function () {
    actingAdminWithRole();
    $confirmed = Order::factory()->withStatus(OrderStatus::Confirmed)->create();
    Order::factory()->withStatus(OrderStatus::Pending)->create();

    $response = $this->getJson(route('admin.orders.index', ['filter' => ['status' => 'confirmed']]));

    $ids = collect($response->json('rows'))->pluck('id');
    expect($ids)->toContain($confirmed->id)->toHaveCount(1);
});

it('searches by order_number and by guest_email', function () {
    actingAdminWithRole();
    $byNumber = Order::factory()->create();
    $byEmail = Order::factory()->guest()->create(['guest_email' => 'unique-search@example.com']);
    Order::factory()->create();

    $byNumberResponse = $this->getJson(route('admin.orders.index', ['q' => $byNumber->order_number]));
    expect(collect($byNumberResponse->json('rows'))->pluck('id'))->toContain($byNumber->id)->toHaveCount(1);

    $byEmailResponse = $this->getJson(route('admin.orders.index', ['q' => 'unique-search@example.com']));
    expect(collect($byEmailResponse->json('rows'))->pluck('id'))->toContain($byEmail->id)->toHaveCount(1);
});

it('never repeats or drops a row across two consecutive sorted pages', function () {
    actingAdminWithRole();

    // More than OrdersTable::perPage() (50), all tied on grand_total, so
    // the id tiebreaker is actually exercised across a real page split.
    Order::factory()->count(75)->create(['grand_total' => 50000]);

    $page1 = collect($this->getJson(route('admin.orders.index', ['sort' => 'grand_total', 'direction' => 'desc', 'page' => 1]))->json('rows'))->pluck('id');
    $page2 = collect($this->getJson(route('admin.orders.index', ['sort' => 'grand_total', 'direction' => 'desc', 'page' => 2]))->json('rows'))->pluck('id');

    expect($page1->intersect($page2))->toHaveCount(0);
    expect($page1->count() + $page2->count())->toBe(75);
});

it('loads the orders list with a fixed query count regardless of row count - no N+1', function () {
    actingAdminWithRole();

    Order::factory()->count(5)->create();
    $this->getJson(route('admin.orders.index'));
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson(route('admin.orders.index'));
    $five = count(DB::getQueryLog());
    DB::disableQueryLog();

    Order::factory()->count(45)->create();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson(route('admin.orders.index'));
    $fifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($five)->toBe($fifty);
});

it('has no route at all for deleting an order', function () {
    expect(\Illuminate\Support\Facades\Route::has('admin.orders.destroy'))->toBeFalse();
});
