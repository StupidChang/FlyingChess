<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects_to_default_locale(): void
    {
        $response = $this->get('/');

        $response->assertStatus(301);
        $response->assertRedirect('/tw');
    }

    public function test_localized_home_returns_successful_response(): void
    {
        $response = $this->withCookie('age_verified', '1')->get('/tw');

        $response->assertStatus(200);
    }
}
