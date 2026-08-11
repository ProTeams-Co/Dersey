<?php

use App\Enums\AdminStatus;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('test@example.com|127.0.0.1');
});

it('locks the login out after 5 failed attempts from the same email+ip', function () {
    Admin::factory()->create(['email' => 'test@example.com', 'status' => AdminStatus::Active]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('admin.login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // The 6th attempt is throttled even with the CORRECT password - proving
    // the lockout blocks by (email, ip), not just repeated bad credentials.
    $response = $this->from(route('admin.login'))->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('admin');
});

it('does not lock out a different email from the same ip', function () {
    Admin::factory()->create(['email' => 'test@example.com']);
    Admin::factory()->create(['email' => 'other@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('admin.login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post(route('admin.login.store'), [
        'email' => 'other@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticated('admin');
});

it('logs a failed login attempt to the activity log', function () {
    Admin::factory()->create(['email' => 'test@example.com']);

    $this->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $logged = Activity::where('log_name', 'admin-auth')->latest('id')->first();

    expect($logged)->not->toBeNull()
        ->and($logged->description)->toBe('login_failed')
        ->and($logged->properties['email'])->toBe('test@example.com');
});
