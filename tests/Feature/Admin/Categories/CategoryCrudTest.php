<?php

use App\Models\Category;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the categories tree view without errors', function () {
    actingAdminWithRole();
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();

    $this->get(route('admin.categories.index'))->assertOk();
});

it('renders the categories search results view without errors', function () {
    actingAdminWithRole();
    Category::factory()->count(3)->create();

    $this->get(route('admin.categories.index', ['q' => 'x']))->assertOk();
});

it('creates a category with translations for both locales', function () {
    actingAdminWithRole();

    $response = $this->post(route('admin.categories.store'), [
        'translations' => [
            'ar' => ['name' => 'تصنيف تجريبي', 'slug' => 'category-test-ar'],
            'en' => ['name' => 'Test Category', 'slug' => 'category-test-en'],
        ],
        'is_active' => '1',
        'is_featured' => '0',
        'show_in_menu' => '1',
        'sort' => 3,
    ]);

    $response->assertRedirect(route('admin.categories.index'));

    $category = Category::first();
    expect($category)->not->toBeNull()
        ->and($category->translate('ar')->name)->toBe('تصنيف تجريبي')
        ->and($category->translate('en')->name)->toBe('Test Category')
        ->and($category->is_active)->toBeTrue();
});

it('updates an existing category', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();

    $response = $this->put(route('admin.categories.update', $category->id), [
        'translations' => [
            'ar' => ['name' => 'اسم محدّث', 'slug' => $category->translate('ar')->slug],
            'en' => ['name' => 'Updated Name', 'slug' => $category->translate('en')->slug],
        ],
        'is_active' => '0',
        'is_featured' => '0',
        'show_in_menu' => '0',
        'sort' => 7,
    ]);

    $response->assertRedirect(route('admin.categories.index'));

    $category->refresh();
    expect($category->translate('ar')->name)->toBe('اسم محدّث')
        ->and($category->is_active)->toBeFalse();
});

it('deletes a leaf category with no products', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();

    $this->delete(route('admin.categories.destroy', $category->id))
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::find($category->id))->toBeNull();
});

it('refuses to delete a category with live children and returns a translated message', function () {
    actingAdminWithRole();
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();

    $response = $this->delete(route('admin.categories.destroy', $parent->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', __('errors.category_has_children'));
    expect(Category::find($parent->id))->not->toBeNull();
});

it('rejects a duplicate slug in the same locale but allows it in a different locale', function () {
    actingAdminWithRole();
    Category::factory()->create()->translate('ar')->update(['slug' => 'shared-slug']);

    $response = $this->post(route('admin.categories.store'), [
        'translations' => [
            'ar' => ['name' => 'تصنيف تاني', 'slug' => 'shared-slug'],
            'en' => ['name' => 'Another Category', 'slug' => 'another-en-slug'],
        ],
        'is_active' => '1',
        'sort' => 0,
    ]);

    $response->assertSessionHasErrors('translations.ar.slug');

    $response = $this->post(route('admin.categories.store'), [
        'translations' => [
            'ar' => ['name' => 'تصنيف تالت', 'slug' => 'different-ar-slug'],
            'en' => ['name' => 'Third Category', 'slug' => 'shared-slug'],
        ],
        'is_active' => '1',
        'sort' => 0,
    ]);

    $response->assertSessionDoesntHaveErrors('translations.en.slug');
});

it('auto-creates a redirect when a category slug changes through the admin edit form', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();
    $oldSlug = $category->translate('ar')->slug;

    $this->put(route('admin.categories.update', $category->id), [
        'translations' => [
            'ar' => ['name' => $category->translate('ar')->name, 'slug' => 'brand-new-slug'],
            'en' => ['name' => $category->translate('en')->name, 'slug' => $category->translate('en')->slug],
        ],
        'is_active' => '1',
        'sort' => 0,
    ]);

    $redirect = Redirect::query()->where('from_path', "/ar/categories/{$oldSlug}")->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/ar/categories/brand-new-slug');
});

it('persists drag-and-drop order and re-parenting through the reorder endpoint', function () {
    actingAdminWithRole();
    $root = Category::factory()->create();
    $first = Category::factory()->child($root)->create();
    $second = Category::factory()->child($root)->create();

    $this->patch(route('admin.categories.reorder', $second->id), [
        'parent_id' => $root->id,
        'before_id' => $first->id,
    ])->assertOk();

    $first->refresh();
    $second->refresh();

    expect($second->_lft)->toBeLessThan($first->_lft)
        ->and($second->parent_id)->toBe($root->id);
});

it('refuses to move a category under its own descendant', function () {
    actingAdminWithRole();
    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent)->create();

    $this->patch(route('admin.categories.reorder', $parent->id), [
        'parent_id' => $child->id,
    ])->assertStatus(422);
});

it('keeps a fixed query count for the tree view regardless of category count', function () {
    actingAdminWithRole();

    Category::factory()->count(5)->create();

    // Warm-up request first - spatie/laravel-permission's role/permission
    // lookup only queries once per process and is cached in memory after,
    // which would otherwise show up as "extra" queries on the very first
    // request and make this comparison meaningless.
    $this->get(route('admin.categories.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.categories.index'))->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    Category::factory()->count(95)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.categories.index'))->assertOk();
    $queriesForHundred = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFive)->toBe($queriesForHundred);
});

it('denies a non-permitted admin from viewing categories', function () {
    actingAdminWithRole('support');

    $this->get(route('admin.categories.index'))->assertForbidden();
});
