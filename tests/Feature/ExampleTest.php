<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /* 首頁會查 boards。沒有 RefreshDatabase 的話這個檔案裡唯一真的會渲染頁面的
       那條測試會 500 —— 以前看不出來,是因為它拿到的是年齡閘,根本沒進到控制器。 */
    use RefreshDatabase;

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
        /* withCookie 送的值會被 EncryptCookies 當成加密內容去解,解不開就等於沒帶 ——
           所以這條測試以前拿到的其實是年齡閘,200 是閘門頁的 200,首頁一次都沒被渲染過。
           改用 asAgeVerified(),並斷言頁面上真的有首頁的內容。 */
        $response = $this->asAgeVerified()->get('/tw');

        $response->assertStatus(200);
        // 首頁自己的 hero 標題 —— 年齡閘那一頁沒有它,所以它能證明真的渲染到首頁
        $response->assertSee('hero-title', false);
    }
}
