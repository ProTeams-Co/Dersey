<?php

use App\Enums\RedirectStatusCode;
use App\Exceptions\RedirectLoopException;
use App\Models\Redirect;
use App\Services\Seo\RedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stops safely instead of looping forever when redirects form a cycle', function () {
    Redirect::create(['from_path' => '/a', 'to_path' => '/b', 'status_code' => RedirectStatusCode::Permanent, 'is_active' => true]);
    Redirect::create(['from_path' => '/b', 'to_path' => '/a', 'status_code' => RedirectStatusCode::Permanent, 'is_active' => true]);

    $service = app(RedirectService::class);

    expect(fn () => $service->resolve('/a'))->toThrow(RedirectLoopException::class);
});

it('resolves a normal (non-looping) chain to its final destination', function () {
    Redirect::create(['from_path' => '/old', 'to_path' => '/mid', 'status_code' => RedirectStatusCode::Permanent, 'is_active' => true]);
    Redirect::create(['from_path' => '/mid', 'to_path' => '/new', 'status_code' => RedirectStatusCode::Permanent, 'is_active' => true]);

    $service = app(RedirectService::class);
    $resolved = $service->resolve('/old');

    expect($resolved)->not->toBeNull()
        ->and($resolved->to_path)->toBe('/new')
        ->and($resolved->status_code)->toBe(RedirectStatusCode::Permanent);
});

it('returns null for a path with no redirect at all', function () {
    $service = app(RedirectService::class);

    expect($service->resolve('/does-not-exist'))->toBeNull();
});
