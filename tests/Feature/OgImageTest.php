<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Services\OgImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 分享卡片(og:image)。
 *
 * 這一組守的是「分享出去看得到東西」這件事,而它有三個各自會靜靜壞掉的環節:
 * 圖產得出來、結果頁指到那張圖、以及各家抓取器拿得到那張圖(不被年齡閘擋住)。
 */
class OgImageTest extends TestCase
{
    use RefreshDatabase;

    /** 沒有中文字型的環境(例如還沒補套件的 CI)畫出來會是一排豆腐字,不是這裡要測的事。 */
    private function skipWithoutFonts(): void
    {
        if (! app(OgImageService::class)->available()) {
            $this->markTestSkipped('環境缺 GD 或缺 CJK 字型 —— 卡片端點會退回站台預設圖');
        }
    }

    public function test_trait_card_is_a_1200x630_png(): void
    {
        $this->skipWithoutFonts();

        $response = $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/trait-test/dominant/og.png')->assertOk();

        $response->assertHeader('Content-Type', 'image/png');

        // 尺寸寫死是刻意的:1200×630 是各家社群平台的大圖規格,改掉就會被裁切。
        $size = getimagesizefromstring($response->content());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }

    public function test_horny_card_is_a_1200x630_png(): void
    {
        $this->skipWithoutFonts();

        $response = $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/dual-control/simmering/og.png')->assertOk();

        $response->assertHeader('Content-Type', 'image/png');

        $size = getimagesizefromstring($response->content());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }

    public function test_every_quadrant_has_a_card(): void
    {
        /* 五個象限頁都會被分享。少畫一張不會報錯,只會讓那一頁的預覽退回站台
           預設圖 —— 而那件事在畫面上看不出來。 */
        $this->skipWithoutFonts();
        $og = app(OgImageService::class);

        foreach (array_keys((array) config('horny.quadrants')) as $key) {
            $png = $og->hornyCard($key);
            $this->assertNotSame('', $png, "象限 {$key} 畫不出卡片");
            $size = getimagesizefromstring($png);
            $this->assertSame([1200, 630], [$size[0], $size[1]], "象限 {$key} 的尺寸不對");
        }
    }

    public function test_the_horny_result_page_points_at_its_own_card(): void
    {
        $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/dual-control/open')
            ->assertOk()
            ->assertSee('/tw/dual-control/open/og.png', false)
            // X 不看 og:type,少了這個只會顯示小方圖
            ->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_the_horny_card_is_reachable_without_passing_the_age_gate(): void
    {
        /* 抓取器不帶 cookie 也不在 UA 白名單裡。拿到年齡確認頁的話預覽就是空白,
           所以這一條**不能**關掉 AgeVerification —— 測的正是它對 og.png 的放行。 */
        config(['content.age_gate_mode' => 'interstitial']);
        $this->skipWithoutFonts();

        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')
            ->get('/tw/dual-control/simmering/og.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_an_unknown_quadrant_slug_is_404(): void
    {
        $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/dual-control/no-such-quadrant/og.png')->assertNotFound();
    }

    public function test_the_horny_fingerprint_follows_its_own_lang_file(): void
    {
        /* 指紋是拿 lang 檔的 mtime 算的,而 kind 決定看哪個檔。kind 對不上檔名的話
           改完色度測驗的文案,分享出去還是舊圖 —— 而且沒有指令清得掉。 */
        $og = app(OgImageService::class);

        $before = $og->fingerprint('horny', 'simmering', 'zh_TW');
        $this->assertSame($before, $og->fingerprint('horny', 'simmering', 'zh_TW'));
        $this->assertNotSame($before, $og->fingerprint('horny', 'open', 'zh_TW'));
        // 不同測驗的同一個 key 也要不同指紋
        $this->assertNotSame($before, $og->fingerprint('trait', 'simmering', 'zh_TW'));

        /* 「語系檔一改就換指紋」不在這裡動真的檔案(那會改到線上那份的 mtime)——
           改 og.version 走的是同一個 hash 輸入,驗的是同一件事:輸入變了指紋就變。
           kind → 檔名的對應由上面那條 trait/horny 不同指紋守住。 */
        config(['og.version' => (int) config('og.version') + 1]);
        $this->assertNotSame($before, $og->fingerprint('horny', 'simmering', 'zh_TW'));
    }

    public function test_unknown_slug_is_404_not_a_blank_card(): void
    {
        $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/trait-test/no-such-type/og.png')->assertNotFound();

        $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/trait-test/no-such-trait/og.png')->assertNotFound();
    }

    public function test_every_trait_and_band_has_its_own_card(): void
    {
        $this->skipWithoutFonts();

        $og = app(OgImageService::class);

        /* 20 型與 5 個級距都要畫得出來。缺一張的症狀是「某幾型分享出去沒有預覽圖」,
           而那要等到有人真的分享才會發現。 */
        foreach (array_keys((array) config('traits.traits')) as $key) {
            $this->assertNotSame('', $og->traitCard($key), "屬性 {$key} 畫不出卡片");
        }

    }

    public function test_result_pages_point_og_image_at_their_own_card(): void
    {
        /* 端點做好了但結果頁還指著站台預設圖 —— 這是最容易發生、也最不容易發現的
           壞法:頁面一切正常,只有分享出去的預覽是錯的。 */
        $this->withoutMiddleware(AgeVerification::class)
            ->get('/tw/trait-test/dominant')
            ->assertOk()
            ->assertSee('/tw/trait-test/dominant/og.png', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_cards_are_reachable_without_passing_the_age_gate(): void
    {
        /* 連結預覽抓取器不帶 cookie、也不在 UA 白名單裡(LINE、Discord、Telegram
           都不在)。它們拿到年齡確認頁的話,預覽就是一片空白 —— 這一條測的是
           AgeVerification 對 og.png 的放行,所以**不能**關掉那個中介層。 */
        config(['content.age_gate_mode' => 'interstitial']);

        $this->skipWithoutFonts();

        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')
            ->get('/tw/trait-test/dominant/og.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // 另一個不在 UA 白名單裡的抓取器,走的是同一條放行
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; U) Line/13.0.0')
            ->get('/tw/trait-test/dominant/og.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_training_bots_still_get_nothing(): void
    {
        /* 放行卡片不能順手把訓練型爬蟲也放進來 —— 那道判斷刻意排在放行之前。 */
        $response = $this->withHeader('User-Agent', 'GPTBot/1.0')
            ->get('/tw/trait-test/dominant/og.png')
            ->assertOk();

        // 拿到的是年齡確認頁那份 HTML,不是 PNG(PNG 的前八個位元組有魔術字串)。
        $this->assertNotSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString('PNG', substr($response->content(), 0, 8));
    }

    public function test_fingerprint_changes_when_the_wording_changes(): void
    {
        /* 卡片文字來自語系檔,而 Facebook 是按網址記憶的。指紋不跟著語系檔變的話,
           改完文案分享出去還是舊圖,而且沒有任何指令清得掉。 */
        $og = app(OgImageService::class);

        $before = $og->fingerprint('trait', 'dom', 'zh_TW');
        $this->assertSame($before, $og->fingerprint('trait', 'dom', 'zh_TW'));

        $this->assertNotSame($before, $og->fingerprint('trait', 'sub', 'zh_TW'));

        config(['og.version' => (int) config('og.version') + 1]);
        $this->assertNotSame($before, $og->fingerprint('trait', 'dom', 'zh_TW'));
    }
}
