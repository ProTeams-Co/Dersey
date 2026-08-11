<?php

namespace Tests\Fixtures;

use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Standalone fixture for testing App\Support\Traits\HasTranslations in
 * isolation — no real translatable model exists yet in Batch 2.1 (the
 * catalog, the first real consumer, lands in a later batch). Table is
 * created/dropped by the test itself, matching the standard
 * {model}_translations shape a real model would use.
 *
 * Named to match its own table (test_fixture_widgets) on purpose —
 * HasTranslations::translations() relies on Eloquent's own default hasMany
 * foreign-key convention (Str::snake(class name) . '_id'), the same as any
 * real model would (Product -> product_id), so the fixture's class name
 * has to agree with its migration's FK column name the same way.
 */
class TestFixtureWidget extends Model
{
    use HasTranslations;

    protected $table = 'test_fixture_widgets';

    protected $guarded = [];

    protected array $translatable = ['title'];

    public function translationModel(): string
    {
        return TestFixtureWidgetTranslation::class;
    }
}
