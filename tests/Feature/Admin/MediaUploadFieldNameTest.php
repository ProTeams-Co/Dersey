<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Regression: MediaUploadController::store() used to hardcode the file
 * field name as "file" - correct for CKEditor 5's upload adapter
 * (editor.js always posts under "file"), but wrong for FilePond, which
 * posts under whatever `name` the <input> it enhanced already had
 * (x-admin.media-picker's own `name` prop: "logo", "icon", "image", ...).
 * Every FilePond upload 422'd on a field that was never sent.
 */
it('accepts an upload under a field name other than "file" (FilePond)', function () {
    Storage::fake('local');
    actingAdminWithRole();

    $response = $this->post(route('admin.media.store'), [
        'logo' => UploadedFile::fake()->image('logo.png'),
    ], ['Accept' => 'application/json']);

    $response->assertOk();
    $response->assertJsonStructure(['id', 'url']);
});

it('still accepts an upload under the "file" field name (CKEditor)', function () {
    Storage::fake('local');
    actingAdminWithRole();

    $response = $this->post(route('admin.media.store'), [
        'file' => UploadedFile::fake()->image('inline.png'),
    ], ['Accept' => 'application/json']);

    $response->assertOk();
    $response->assertJsonStructure(['id', 'url']);
});

it('returns a JSON validation error, not an HTML page, when the request expects JSON', function () {
    actingAdminWithRole();

    $response = $this->post(route('admin.media.store'), [], ['Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertHeader('content-type', 'application/json');
});
