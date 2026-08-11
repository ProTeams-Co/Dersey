<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

it('falls back to the default locale when the requested translation is missing', function () {
    $widget = TestFixtureWidget::create();
    $widget->translations()->create(['locale' => 'en', 'title' => 'Fallback Title']);
    // No 'ar' translation created on purpose.

    $fresh = TestFixtureWidget::withCurrentTranslation('ar')->find($widget->id);

    expect($fresh->translate('ar')->title)->toBe('Fallback Title');
});

it('resolves a translatable attribute for the current locale automatically', function () {
    $widget = TestFixtureWidget::create();
    $widget->translations()->createMany([
        ['locale' => 'ar', 'title' => 'عنوان عربي'],
        ['locale' => 'en', 'title' => 'English Title'],
    ]);

    $arabic = TestFixtureWidget::withCurrentTranslation('ar')->find($widget->id);
    $english = TestFixtureWidget::withCurrentTranslation('en')->find($widget->id);

    expect($arabic->title)->toBe('عنوان عربي')
        ->and($english->title)->toBe('English Title');
});

it('filters by a translated field in a specific locale via whereTranslation', function () {
    $match = TestFixtureWidget::create();
    $match->translations()->create(['locale' => 'ar', 'title' => 'مطابق']);

    $other = TestFixtureWidget::create();
    $other->translations()->create(['locale' => 'ar', 'title' => 'غير مطابق']);

    $results = TestFixtureWidget::whereTranslation('title', 'مطابق', 'ar')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($match->id);
});
