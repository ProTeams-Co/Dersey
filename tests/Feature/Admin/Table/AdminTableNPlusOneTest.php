<?php

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * A column whose format() reads a translated attribute - only safe from
 * N+1 because with() eager-loads the relation up front (this batch's
 * explicit "filters/columns must not N+1" requirement).
 */
class NPlusOneAdminTableFixture extends AdminTable
{
    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'name', 'format' => fn (Category $row) => e($row->name)],
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function query(): Builder
    {
        return Category::query();
    }

    public function with(): array
    {
        return ['translations'];
    }

    public function perPage(): int
    {
        return 200;
    }
}

function seedCategoriesForNPlusOne(int $count): void
{
    foreach (range(1, $count) as $i) {
        $category = Category::create(['sort' => $i, 'is_active' => true, 'is_featured' => false, 'show_in_menu' => true]);
        CategoryTranslation::create(['category_id' => $category->id, 'locale' => 'ar', 'name' => "تصنيف {$i}"]);
    }
}

it('keeps the query count fixed at 100 rows vs 5 rows when rendering a formatted, translated column', function () {
    seedCategoriesForNPlusOne(5);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $table = new NPlusOneAdminTableFixture(Request::create('/', 'GET'));
    foreach ($table->paginator() as $row) {
        $table->formatRow($row);
    }

    $queriesForFiveRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    seedCategoriesForNPlusOne(95); // 100 rows total now

    DB::flushQueryLog();
    DB::enableQueryLog();

    $table = new NPlusOneAdminTableFixture(Request::create('/', 'GET'));
    foreach ($table->paginator() as $row) {
        $table->formatRow($row);
    }

    $queriesForHundredRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFiveRows)->toBe($queriesForHundredRows);
});
