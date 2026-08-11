<?php

namespace Tests\Fixtures;

use App\Models\Translation;

class TestFixtureWidgetTranslation extends Translation
{
    protected $table = 'test_fixture_widget_translations';

    protected $fillable = ['test_fixture_widget_id', 'locale', 'title'];
}
