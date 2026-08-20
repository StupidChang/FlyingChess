<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * 五個迷你遊戲頁的節流。
 *
 * 這幾頁會把題庫的一部分送進 HTML,所以有 40/分鐘 的上限擋整批枚舉。但
 * `throttle:40,1` 這種匿名寫法的計數器 key 是 sha1(domain|ip) —— 不含路由,
 * 全站的匿名節流路由共用同一個 per-IP 計數器。實際壞掉的樣子:一場飛行棋
 * 每 2 秒輪詢一次 state,一分鐘 30 次,加上動作就超過 40 —— 然後那位玩家
 * (以及同一個 NAT 出口的所有人、還有 Googlebot)去點這五頁全部拿到 429,
 * 而這五頁都在 sitemap 裡。
 *
 * 所以改用具名節流器 minigame-page,bucket 縮到「這一頁 + 這個 IP」。
 * 這支測試釘住那個界線:上限還在,但不會外溢到別的頁。
 */
class MiniGameThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('minigame-page');
    }

    public function test_hitting_one_minigame_page_does_not_throttle_the_others(): void
    {
        // 燒掉 dice-game 這一頁的額度
        for ($i = 0; $i < 40; $i++) {
            $this->get('/tw/dice-game')->assertOk();
        }
        $this->get('/tw/dice-game')->assertStatus(429);

        // 其餘四頁不該被連坐
        foreach (['card-game', 'king-game', 'wheel-game', 'who-most-likely'] as $page) {
            $this->get("/tw/{$page}")->assertOk();
        }
    }

    public function test_locales_of_the_same_page_share_one_budget(): void
    {
        /* 刻意的:題庫是同一批、只是換了翻譯,輪流換語系不該拿到四倍額度。 */
        for ($i = 0; $i < 40; $i++) {
            $this->get('/tw/card-game')->assertOk();
        }

        $this->get('/cn/card-game')->assertStatus(429);
    }

    public function test_other_throttled_routes_do_not_consume_the_minigame_budget(): void
    {
        /* 這是原本的病灶:遊戲中輪詢會把迷你遊戲頁的額度吃光。
           bucket-list 的分享頁是另一條匿名節流路由(throttle:60,1)。 */
        for ($i = 0; $i < 45; $i++) {
            $this->get('/tw/bucket-list/NOSUCHCODE');
        }

        $this->get('/tw/card-game')->assertOk();
    }
}
