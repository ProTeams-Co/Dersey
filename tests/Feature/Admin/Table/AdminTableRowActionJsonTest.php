<?php

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends already-translated labels and rendered icon markup for row actions in the JSON response', function () {
    actingAdminWithRole();
    Brand::factory()->create();

    $response = $this->get(route('admin.brands.index'), ['Accept' => 'application/json']);
    $response->assertOk();

    $action = collect($response->json('rows'))->first()['_actions'][0];

    // Regression: admin/table.js used to render the raw untranslated lang
    // key (e.g. "admin.table.actions_edit") as plain text with no icon at
    // all after any Ajax-driven refresh (search/sort/filter/paginate/bulk
    // action) - only visible by actually clicking around in a browser, not
    // from an HTTP-status-only test.
    expect($action['label'])->not->toBe('admin.table.actions_edit')
        ->and($action['label'])->toBe(__('admin.table.actions_edit'))
        ->and($action['icon_html'])->toContain('<svg');
});

it('marks a non-GET row action with its HTTP method in the JSON response', function () {
    actingAdminWithRole();
    Brand::factory()->create();

    $response = $this->get(route('admin.brands.index'), ['Accept' => 'application/json']);
    $deleteAction = collect($response->json('rows'))->first()['_actions'][1];

    expect($deleteAction['method'])->toBe('delete');
});
