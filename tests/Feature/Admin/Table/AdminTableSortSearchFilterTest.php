<?php

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('mysql-critical');

/**
 * A minimal, test-only AdminTable over Category - exercises plain-column
 * search (slug), translated-column search (name, through
 * category_translations + ArabicNormalizer), a select filter (is_active),
 * and sorting, against a real translatable model rather than a fixture
 * with no HasTranslations involved.
 */
class CategoriesAdminTableFixture extends AdminTable
{
    public function columns(): array
    {
        return [
            ['key' => 'sort', 'label' => 'sort', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'is_active', 'sortable' => true],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'is_active', 'type' => 'boolean', 'label' => 'is_active'],
        ];
    }

    public function query(): Builder
    {
        return Category::query();
    }

    public function translatedSearchColumns(): array
    {
        return ['name'];
    }

    public function with(): array
    {
        return ['translations'];
    }
}

function makeCategoryFixture(string $nameAr, int $sort, bool $isActive = true): Category
{
    $category = Category::create(['sort' => $sort, 'is_active' => $isActive, 'is_featured' => false, 'show_in_menu' => true]);

    CategoryTranslation::create(['category_id' => $category->id, 'locale' => 'ar', 'name' => $nameAr]);
    CategoryTranslation::create(['category_id' => $category->id, 'locale' => 'en', 'name' => $nameAr]);

    return $category;
}

it('sorts by a plain column in both directions', function () {
    makeCategoryFixture('أ', 3);
    makeCategoryFixture('ب', 1);
    makeCategoryFixture('ج', 2);

    $asc = (new CategoriesAdminTableFixture(Request::create('/', 'GET', ['sort' => 'sort', 'direction' => 'asc'])))->paginator();
    expect($asc->pluck('sort')->all())->toBe([1, 2, 3]);

    $desc = (new CategoriesAdminTableFixture(Request::create('/', 'GET', ['sort' => 'sort', 'direction' => 'desc'])))->paginator();
    expect($desc->pluck('sort')->all())->toBe([3, 2, 1]);
});

it('falls back to the default sort for a non-sortable/unknown column', function () {
    makeCategoryFixture('أ', 3);
    makeCategoryFixture('ب', 1);

    $table = new CategoriesAdminTableFixture(Request::create('/', 'GET', ['sort' => 'not_a_real_column']));

    expect($table->currentSort()['key'])->toBe('id');
});

it('filters rows by a boolean filter', function () {
    makeCategoryFixture('نشط', 1, true);
    makeCategoryFixture('غير نشط', 2, false);

    $table = new CategoriesAdminTableFixture(Request::create('/', 'GET', ['filter' => ['is_active' => '1']]));

    expect($table->paginator())->toHaveCount(1)
        ->and($table->paginator()->first()->is_active)->toBeTrue();
});

it('searches translated fields through the translation table, normalized', function () {
    app()->setLocale('ar');

    makeCategoryFixture('فستان سهرة', 1);
    makeCategoryFixture('بنطلون جينز', 2);

    // "فُستان" (with a diacritic) must still match the stored "فستان" -
    // this is the whole point of routing search through ArabicNormalizer.
    $table = new CategoriesAdminTableFixture(Request::create('/', 'GET', ['q' => 'فُستان']));

    expect($table->paginator())->toHaveCount(1);
});

it('does not match an unrelated translated term', function () {
    app()->setLocale('ar');

    makeCategoryFixture('فستان سهرة', 1);
    makeCategoryFixture('بنطلون جينز', 2);

    $table = new CategoriesAdminTableFixture(Request::create('/', 'GET', ['q' => 'حذاء']));

    expect($table->paginator())->toHaveCount(0);
});
