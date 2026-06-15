<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root redirect negotiates locale via Accept-Language
     * (see LocaleHelper::detectFromRequest: URL prefix > cookie >
     * Accept-Language > default). Symfony test requests send
     * "Accept-Language: en-us,en;q=0.5" by default, so we set the
     * header explicitly for deterministic assertions.
     */
    public function test_root_redirects_to_default_locale_when_no_accept_language(): void
    {
        // Empty Accept-Language → falls back to the app default (zh_TW → /tw).
        $response = $this->get('/', ['Accept-Language' => '']);

        $response->assertStatus(301);
        $response->assertRedirect('/tw');
    }

    public function test_root_redirects_traditional_chinese_browser_to_tw(): void
    {
        $response = $this->get('/', ['Accept-Language' => 'zh-TW,zh;q=0.9']);

        $response->assertStatus(301);
        $response->assertRedirect('/tw');
    }

    public function test_root_redirects_english_browser_to_en(): void
    {
        $response = $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9']);

        $response->assertStatus(301);
        $response->assertRedirect('/en');
    }

    public function test_localized_home_returns_successful_response(): void
    {
        $response = $this->withCookie('age_verified', '1')->get('/tw');

        $response->assertStatus(200);
    }
}
