<?php

use App\Support\Lang\TranslationParityChecker;

it('redirects the bare root with a 302, never a 301', function () {
    // Not assertRedirect('/ar') as an exact string — the target is a full
    // absolute URL, and which locale it lands on depends on Accept-Language
    // (covered separately below). What this test guards is the status code.
    $response = $this->get('/', ['Accept-Language' => 'ar']);

    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toEndWith('/ar');
});

it('respects Accept-Language for the bare root redirect', function () {
    $response = $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9']);

    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toEndWith('/en');
});

it('sends bots to the default locale regardless of Accept-Language', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'en-GB,en;q=0.9',
        'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    ])->get('/');

    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toEndWith('/ar');
});

it('prefers a returning visitor\'s cookie over the current Accept-Language', function () {
    $response = $this->withCookie('locale', 'en')->get('/', ['Accept-Language' => 'ar']);

    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toEndWith('/en');
});

it('serves /ar and /en directly', function () {
    $this->get('/ar')->assertOk();
    $this->get('/en')->assertOk();
});

it('returns 404 for an unsupported locale segment, never a redirect', function () {
    $this->get('/xx')->assertNotFound();
    $this->get('/xx/anything')->assertNotFound();
});

it('serves /admin directly with no locale prefix, redirecting only to the admin login (never a locale)', function () {
    // Since Batch 3.0, /admin is the real dashboard route, guarded by
    // admin.auth - an unauthenticated request 302s to /admin/login, not a
    // locale-prefixed URL. That redirect target (not a 404, not a
    // {locale}/... URL) is still proof /admin sits outside the locale
    // system, same as the old TODO(3.0) ping route proved before it existed.
    $response = $this->get('/admin');

    $response->assertRedirect(route('admin.login'));
});

it('does not let the locale prefix capture /admin', function () {
    // The real proof: if {locale}/admin also matched, the exclusion isn't
    // working — it would just mean routes/admin.php's own paths overlap
    // with the locale group's by coincidence.
    $this->get('/ar/admin')->assertNotFound();
    $this->get('/en/admin')->assertNotFound();
});

it('does not redirect to a locale or run SetLocale for /admin regardless of Accept-Language', function () {
    $response = $this->get('/admin', ['Accept-Language' => 'en-GB,en;q=0.9']);

    $response->assertRedirect(route('admin.login'));
    expect(app()->getLocale())->toBe(config('app.locale'));
});

it('points the canonical link on an Arabic page at the Arabic URL', function () {
    $response = $this->get('/ar');

    $response->assertOk();
    $response->assertSee('<link rel="canonical" href="'.e(url('/ar')).'">', false);
});

it('points hreflang x-default at the Arabic URL on both locales', function () {
    $expected = '<link rel="alternate" hreflang="x-default" href="'.e(url('/ar')).'">';

    $this->get('/ar')->assertSee($expected, false);
    $this->get('/en')->assertSee($expected, false);
});

it('has a matching translation key in en for every key in ar, and vice versa', function () {
    $diff = TranslationParityChecker::diff();

    expect($diff['missing_in_en'])->toBe([]);
    expect($diff['missing_in_ar'])->toBe([]);
});
