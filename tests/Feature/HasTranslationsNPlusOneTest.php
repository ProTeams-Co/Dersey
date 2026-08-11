<?php

use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\TestFixtureWidget;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('test_fixture_widgets', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('test_fixture_widget_translations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('test_fixture_widget_id')->constrained('test_fixture_widgets')->cascadeOnDelete();
        $table->string('locale');
        $table->string('title');
        $table->timestamps();

        $table->unique(['test_fixture_widget_id', 'locale']);
        $table->index('locale');
    });

    config(['app.fallback_locale' => 'en']);
});

afterEach(function () {
    Schema::dropIfExists('test_fixture_widget_translations');
    Schema::dropIfExists('test_fixture_widgets');
});

function createTestFixtureWidgets(int $count): void
{
    foreach (range(1, $count) as $i) {
        $widget = TestFixtureWidget::create();
        $widget->translations()->create(['locale' => 'ar', 'title' => "Title {$i}"]);
    }
}

it('throws instead of silently N+1-ing when translations are read without eager loading', function () {
    // preventLazyLoading is on outside production (AppServiceProvider,
    // Batch 1.1) — including in the "testing" environment these tests run
    // under, so this is the real behavior a developer hits locally too,
    // not a special case invented for this test.
    createTestFixtureWidgets(20);

    $widgets = TestFixtureWidget::all(); // deliberately no withCurrentTranslation()

    DB::flushQueryLog();
    DB::enableQueryLog();

    $threw = false;
    $queriesBeforeException = null;

    try {
        foreach ($widgets as $widget) {
            $widget->translate('ar');
        }
    } catch (LazyLoadingViolationException) {
        $threw = true;
        $queriesBeforeException = count(DB::getQueryLog());
    }

    DB::disableQueryLog();

    expect($threw)->toBeTrue();
    // Fails on the very first widget's access, before running any query at
    // all - not after quietly firing some number of N+1 queries first.
    expect($queriesBeforeException)->toBe(0);
});

it('keeps the query count fixed at 2 regardless of row count when using withCurrentTranslation', function () {
    createTestFixtureWidgets(5);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $widgets = TestFixtureWidget::withCurrentTranslation('ar')->get();
    foreach ($widgets as $widget) {
        $widget->translate('ar');
    }

    $queriesForFiveRows = count(DB::getQueryLog());
    DB::disableQueryLog(); // stop capturing before creating more rows below

    createTestFixtureWidgets(20); // 25 rows total now

    DB::flushQueryLog();
    DB::enableQueryLog();

    $widgets = TestFixtureWidget::withCurrentTranslation('ar')->get();
    foreach ($widgets as $widget) {
        $widget->translate('ar');
    }

    $queriesForTwentyFiveRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Exact equality, not "fewer than the N+1 case" - 2 queries (widgets,
    // then their translations via a single whereIn) no matter how many
    // rows are involved is the actual claim being tested, and a "fewer"
    // assertion would still pass for an accidental N+1 that just happens
    // to be cheaper than the naive one.
    expect($queriesForFiveRows)->toBe(2)
        ->and($queriesForTwentyFiveRows)->toBe(2)
        ->and($queriesForTwentyFiveRows)->toBe($queriesForFiveRows);
});
