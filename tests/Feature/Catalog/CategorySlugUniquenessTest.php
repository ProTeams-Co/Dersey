<?php

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('mysql-critical');

it('rejects a duplicate slug within the same locale but allows the same slug string across different locales', function () {
    $categoryA = Category::create(['is_active' => true]);
    $categoryB = Category::create(['is_active' => true]);

    CategoryTranslation::create([
        'category_id' => $categoryA->id,
        'locale' => 'ar',
        'name' => 'فستان سهرة',
        'slug' => 'evening-dress',
    ]);

    expect(fn () => CategoryTranslation::create([
        'category_id' => $categoryB->id,
        'locale' => 'ar',
        'name' => 'اسم مختلف تمامًا',
        'slug' => 'evening-dress',
    ]))->toThrow(QueryException::class);

    CategoryTranslation::create([
        'category_id' => $categoryB->id,
        'locale' => 'en',
        'name' => 'Evening Dress',
        'slug' => 'evening-dress',
    ]);

    expect(CategoryTranslation::where('slug', 'evening-dress')->count())->toBe(2);
});
