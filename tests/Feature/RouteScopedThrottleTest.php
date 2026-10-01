<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 匿名的 `throttle:N,1` 要依路由各自計數。
 *
 * Laravel 預設的 key 只有 IP,全站所有節流路由共用一個額度 —— 上線前的全站檢查
 * 爬 sitemap 爬到一半就開始 429,一分鐘後才恢復。
 */
class RouteScopedThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_using_up_one_routes_quota_does_not_block_another_route(): void
    {
        // /discover 是 throttle:60,1;把它的額度用完
        for ($i = 0; $i < 60; $i++) {
            $this->asAgeVerified()->get('/tw/discover');
        }
        $this->asAgeVerified()->get('/tw/discover')->assertStatus(429);

        // 另一條同樣是 throttle:60,1 的路由不受影響
        $this->asAgeVerified()->get('/tw/play/share/NOPE0000')->assertStatus(404);
    }
}
