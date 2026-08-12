<?php

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression: AdminController::respond()'s AJAX/JSON branch used to return
 * only {message} and silently discard the redirect target - core/form.js
 * (every x-admin.form submission) had nothing to act on, so a successful
 * save via the admin UI left the user on the exact same page with no
 * navigation, no visible confirmation, and a re-enabled Save button -
 * inviting exactly the "I can click Save more than once" behavior reported.
 */
it('includes a redirect target and flashes the status message for an AJAX save', function () {
    actingAdminWithRole();
    $brand = Brand::factory()->create();

    $response = $this->put(route('admin.brands.update', $brand->id), [
        'translations' => [
            'ar' => ['name' => $brand->translate('ar')->name, 'slug' => $brand->translate('ar')->slug],
            'en' => ['name' => $brand->translate('en')->name, 'slug' => $brand->translate('en')->slug],
        ],
        'is_active' => '1',
        'sort' => 0,
    ], ['Accept' => 'application/json']);

    $response->assertOk();
    $response->assertJson([
        'message' => __('admin.crud.updated'),
        'redirect' => route('admin.brands.index'),
    ]);

    // The very next request (the client-side redirect core/form.js now
    // performs) should see the flashed status - this is what
    // layouts/admin.blade.php's new session('status') block renders.
    expect(session('status'))->toBe(__('admin.crud.updated'));
});
