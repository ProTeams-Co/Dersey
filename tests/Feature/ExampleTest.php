<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * "/" itself now redirects (302, to /ar or /en — see
     * tests/Feature/LocalizationTest.php) as of Batch 1.3, so this asserts
     * against a route that actually renders a page instead.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/ar');

        $response->assertStatus(200);
    }
}
