<?php

use App\Enums\AdminStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Admin;
use App\Models\Category;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * A throwaway table/controller pair, registered only inside this test, to
 * exercise AdminController::destroy() against a model whose Observer
 * throws a domain exception on a blocked delete
 * (CategoryHasDependentsException, from Batch 2.2) - proving the base
 * controller lets it self-render (translated message, real HTTP status)
 * instead of letting it fall through to a generic 500.
 *
 * A real CategoryPolicy is 3.1+ scope (catalog CRUD) - Gate::policy() is
 * registered ad hoc below instead, scoped to this test only, so the test
 * isolates AdminController's exception-handling behavior without
 * depending on unrelated Policy-authoring work.
 */
class DeleteExceptionCategoriesTableFixture extends AdminTable
{
    public function columns(): array
    {
        return [['key' => 'id', 'label' => 'id']];
    }

    public function filters(): array
    {
        return [];
    }

    public function query(): Builder
    {
        return Category::query();
    }
}

class DeleteExceptionCategoriesControllerFixture extends AdminController
{
    protected function newModel(): Model
    {
        return new Category;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new DeleteExceptionCategoriesTableFixture($request);
    }
}

class AllowAllCategoryPolicyFixture
{
    public function __call($method, $args)
    {
        return true;
    }
}

beforeEach(function () {
    Gate::policy(Category::class, AllowAllCategoryPolicyFixture::class);

    Route::middleware('web')->delete(
        '/__test/categories/{id}',
        [DeleteExceptionCategoriesControllerFixture::class, 'destroy']
    );

    // respond()'s redirect target - AdminController::routeName('index')
    // resolves to "admin.categories.index", which doesn't exist yet (real
    // catalog routes are 3.1+); this stub just needs to exist for the
    // redirect() call itself to succeed, its content is irrelevant here.
    Route::middleware('web')->get('/__test/categories', fn () => 'ok')->name('admin.categories.index');

    app('router')->getRoutes()->refreshNameLookups();
});

it('lets a blocked delete self-render a translated error instead of a 500', function () {
    $admin = Admin::factory()->create(['status' => AdminStatus::Active]);
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create(); // gives $parent a live child

    $response = $this->actingAs($admin, 'admin')->delete("/__test/categories/{$parent->id}");

    $response->assertStatus(302);
    $response->assertSessionHas('error');
    expect($response->getSession()->get('error'))->not->toContain('Server Error')
        ->and(Category::find($parent->id))->not->toBeNull();
});

it('deletes cleanly when nothing blocks it', function () {
    $admin = Admin::factory()->create(['status' => AdminStatus::Active]);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin, 'admin')->delete("/__test/categories/{$category->id}");

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect(Category::find($category->id))->toBeNull();
});
