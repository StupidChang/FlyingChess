<?php

namespace Tests\Feature;

use App\Http\Middleware\DeterScrapers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 擋爬站工具與一次扒整站,但不能擋到真人與搜尋引擎。
 */
class DeterScrapersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_scraping_tools_are_refused(): void
    {
        foreach (['python-requests/2.31', 'Scrapy/2.11 (+https://scrapy.org)', 'Wget/1.21', 'HTTrack 3.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 HeadlessChrome/120.0'] as $ua) {
            $this->withHeader('User-Agent', $ua)->get('/tw')->assertForbidden();
        }
    }

    public function test_browsers_search_engines_and_link_previews_get_through(): void
    {
        foreach (['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'facebookexternalhit/1.1', 'Twitterbot/1.0'] as $ua) {
            $this->withHeader('User-Agent', $ua)->get('/tw')->assertOk();
        }
    }

    public function test_machine_endpoints_are_not_judged_by_user_agent(): void
    {
        $this->withHeader('User-Agent', 'Go-http-client/1.1')->get('/up')->assertOk();
        $this->withHeader('User-Agent', 'python-requests/2.31')->get('/sitemap.xml')->assertOk();
    }

    public function test_pulling_too_many_pages_in_a_minute_is_slowed_down(): void
    {
        for ($i = 0; $i < DeterScrapers::PAGES_PER_MINUTE; $i++) {
            $this->get('/tw/privacy');
        }
        $this->get('/tw/privacy')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_a_fake_googlebot_is_slowed_down_like_anyone_else(): void
    {
        // 127.0.0.1 反查是 localhost,不是 Google 的網域
        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        for ($i = 0; $i < DeterScrapers::PAGES_PER_MINUTE; $i++) {
            $this->withHeader('User-Agent', $ua)->get('/tw/privacy');
        }
        $this->withHeader('User-Agent', $ua)->get('/tw/privacy')->assertStatus(429);
    }

    public function test_a_verified_googlebot_is_never_slowed_down(): void
    {
        Cache::put('verified-crawler:127.0.0.1', true, 60);
        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        for ($i = 0; $i <= DeterScrapers::PAGES_PER_MINUTE; $i++) {
            $this->withHeader('User-Agent', $ua)->get('/tw/privacy');
        }
        $this->withHeader('User-Agent', $ua)->get('/tw/privacy')->assertOk();
    }
}
