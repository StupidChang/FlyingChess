<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 性壓抑指數測驗已經併進色度測驗。
 *
 * 它那 40 題就是色度測驗的「保守程度」那條軸,兩份並存只會互相吃關鍵字,而且舊那
 * 份的內容是新那份的子集。所以整組 301 過去。
 *
 * 這一組守的是**轉址本身**:301 是給搜尋引擎的訊號,悄悄壞掉(變成 404,或變成
 * 302)不會有任何人回報,而外面的連結與已收錄的網址會直接掉。
 */
class RepressionTestRetiredTest extends TestCase
{
    // 首頁與大廳會查 boards
    use RefreshDatabase;

    public function test_the_old_index_permanently_redirects_to_the_new_test(): void
    {
        $this->asAgeVerified()
            ->get('/tw/repression-test')
            ->assertStatus(301)
            ->assertRedirect('/tw/dual-control');
    }

    public function test_the_five_old_band_pages_all_land_on_the_new_test(): void
    {
        /* 不做「級距 → 象限」的映射:舊的五格是單軸切出來的,和新的四個角沒有乾淨
           的一對一關係。硬湊會把人送到一頁講的不是他當初讀到的東西。 */
        foreach (['very-low', 'low', 'moderate', 'high', 'very-high'] as $slug) {
            $this->asAgeVerified()
                ->get("/tw/repression-test/{$slug}")
                ->assertStatus(301)
                ->assertRedirect('/tw/dual-control');
        }
    }

    public function test_the_old_share_cards_redirect_too(): void
    {
        // 那些圖只被它自己的結果頁引用,但已經被抓過的網址還是會再來
        $this->asAgeVerified()
            ->get('/tw/repression-test/high/og.png')
            ->assertStatus(301);
    }

    public function test_the_redirect_keeps_the_locale(): void
    {
        // 轉址掉語系的話,英文訪客會被丟到中文頁
        $this->asAgeVerified()->get('/en/repression-test')->assertRedirect('/en/dual-control');
        $this->asAgeVerified()->get('/jp/repression-test')->assertRedirect('/jp/dual-control');
    }

    public function test_nothing_still_points_at_the_retired_pages(): void
    {
        /* 站內連到 301 是浪費爬取預算,而且使用者按下去會多跳一次。首頁、遊戲大廳、
           導覽列、llms.txt 都改指新測驗了 —— 這一條確認沒有漏。 */
        foreach (['/tw', '/tw/game-hall', '/tw/guide', '/tw/dual-control'] as $path) {
            $this->asAgeVerified()->get($path)
                ->assertOk()
                ->assertDontSee('repression-test', false);
        }

        $this->get('/sitemap-tw.xml')->assertOk()->assertDontSee('repression-test', false);
        $this->get('/llms.txt')->assertOk()->assertDontSee('repression-test', false);
    }
}
