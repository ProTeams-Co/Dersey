<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    'store' => [
        'currency' => env('STORE_CURRENCY', 'EGP'),
        'phone' => env('STORE_PHONE'),
        'email' => env('STORE_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media conversion presets
    |--------------------------------------------------------------------------
    |
    | Consumed by models that implement Spatie\MediaLibrary\HasMedia when they
    | register their conversions. Kept here (rather than hardcoded per model)
    | so every image collection in the app follows the same sizes/formats.
    |
    */

    'media' => [
        'sizes' => [320, 480, 768, 1024, 1440],
        'formats' => ['webp', 'avif', 'jpeg'],
        'strip_exif' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed document URLs
    |--------------------------------------------------------------------------
    |
    | TTL, in minutes, for temporary signed URLs used to serve documents from
    | the "private" disk (invoices, payment receipts). See CLAUDE.md — these
    | documents must never be served from a public disk.
    |
    */

    'signed_url_ttl' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default super-admin account
    |--------------------------------------------------------------------------
    |
    | Seeded by RolePermissionSeeder with the super-admin role — values live
    | in .env only, never hardcoded here (CLAUDE.md). If SUPER_ADMIN_EMAIL
    | or SUPER_ADMIN_PASSWORD is left empty, the seeder skips account
    | creation entirely rather than creating one with a guessable password.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

];
