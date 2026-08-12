<?php

use Illuminate\Support\Facades\Blade;

/**
 * Regression: a checkbox with no explicit value="" attribute submits the
 * literal string "on" when checked (browser default) - Laravel's `boolean`
 * validation rule accepts 1/0/"1"/"0"/true/false, but NOT "on", so every
 * admin form using x-form.toggle failed validation on save the moment a
 * toggle was actually turned on through a real browser, even though every
 * Pest test posted '1'/'0' directly and never caught it.
 */
it('renders an explicit value="1" on the toggle checkbox so a checked submit is a valid boolean', function () {
    $html = (string) Blade::render('<x-form.toggle name="is_active" label="Active" :checked="true" />');

    expect($html)->toContain('value="1"');
});
