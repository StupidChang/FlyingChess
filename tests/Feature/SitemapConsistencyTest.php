<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Support\LocaleHelper;
use Database\Seeders\BoardSeeder;
use Database\Seeders\BoardTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * sitemap 與頁面自己的宣告必須一致。
 *
 * 這是一整類「靜靜壞掉」的問題:sitemap 的查詢條件和頁面的 robots meta 各寫在
 * 兩個檔案裡,只要有一邊改了就會走鐘,而且從外面完全看不出來 —— 頁面照樣顯示、
 * sitemap 照樣是合法的 XML。實際踩到的兩種:
 *
 *   1. 付費範本被列進 sitemap,但訪客(含 Googlebot)拿到 302 轉去付費頁。
 *      在 Search Console 是一筆錯誤,而且白吃掉新網域本來就很少的爬取預算。
 *   2. 範本與社群棋盤被列進 sitemap,頁面自己卻標 noindex。等於一邊喊「收錄我」
 *      一邊喊「不要收錄我」。
 *
 * 兩者現在都由 Board::isPubliclyIndexable() 這一份規則決定,下面這幾條就是防止
 * 它們再度分家。
 */
class SitemapConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, string> sitemap 裡的所有路徑 */
    private function sitemapPaths(): array
    {
        $xml = $this->get('/sitemap-tw.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);

        $this->assertNotEmpty($m[1], 'sitemap 是空的');

        return array_map(fn ($url) => parse_url($url, PHP_URL_PATH), $m[1]);
    }

    public function test_every_sitemap_url_returns_200_and_is_indexable(): void
    {
        $this->seed(BoardSeeder::class);
        $this->seed(BoardTemplateSeeder::class);

        $titles = [];
        $descriptions = [];

        foreach ($this->sitemapPaths() as $path) {
            $html = $this->asAgeVerified()->get($path)
                ->assertOk("sitemap 裡的 {$path} 沒有回 200")
                ->getContent();

            preg_match('#<meta name="robots" content="([^"]*)"#', $html, $r);
            $this->assertSame(
                'index,follow',
                $r[1] ?? '(缺少)',
                "sitemap 收錄了 {$path},但那一頁自己標的是 noindex"
            );

            // 每一頁只能有一個 h1。0 個等於沒有主題,2 個等於主題不明。
            $this->assertSame(
                1,
                preg_match_all('#<h1[\s>]#', $html),
                "{$path} 的 h1 數量不是 1"
            );

            preg_match('#<title>(.*?)</title>#s', $html, $t);
            preg_match('#<meta name="description" content="(.*?)"#s', $html, $d);
            $titles[$path] = trim($t[1] ?? '');
            $descriptions[$path] = trim($d[1] ?? '');
        }

        /* 共用 title / description 的頁面會自己跟自己搶排名,而且 Google 常常
           只收錄其中一個。整站爬一輪本來就要做,順便在這裡守住。 */
        foreach (['title' => $titles, 'description' => $descriptions] as $label => $values) {
            /* 把「值 => 用到它的所有路徑」整理出來,只留下超過一頁的。
               不用 array_diff_assoc:那個只會回報後出現的那一筆,看不到它跟誰撞,
               訊息裡少了另外一半,debug 的時候還要自己再查一次。 */
            $byValue = [];
            foreach ($values as $path => $value) {
                $byValue[$value][] = $path;
            }
            $collisions = array_filter($byValue, fn ($paths) => count($paths) > 1);

            $this->assertSame(
                [],
                $collisions,
                "有頁面共用同一個 {$label}:\n".json_encode($collisions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }

    public function test_premium_templates_are_not_offered_to_crawlers(): void
    {
        $this->seed(BoardSeeder::class);
        $this->seed(BoardTemplateSeeder::class);

        $premium = Board::where('is_premium_template', true)->whereNotNull('share_code')->first();

        if (! $premium) {
            $this->markTestSkipped('這個環境沒有付費範本');
        }

        // 沒有付費資格的訪客會被導去付費頁 —— 所以這種網址不該出現在 sitemap
        $this->asAgeVerified()->get('/tw/play/share/'.$premium->share_code)->assertRedirect();

        $this->assertNotContains(
            '/tw/play/share/'.$premium->share_code,
            $this->sitemapPaths(),
            '付費範本被列進 sitemap,但它對爬蟲只會回 302'
        );
    }

    public function test_the_same_board_reached_two_ways_declares_one_canonical(): void
    {
        $this->seed(BoardSeeder::class);
        $this->seed(BoardTemplateSeeder::class);

        $board = Board::publiclyIndexable()->whereNotNull('share_code')
            ->where('is_default', false)->first();

        if (! $board) {
            $this->markTestSkipped('這個環境沒有可索引的非預設棋盤');
        }

        /* 同一張棋盤有 /play/{id} 與 /play/share/{code} 兩個網址,內容一樣。
           以前兩邊都用 url()->current(),等於各自宣告自己是正本 —— 重複內容。 */
        $canonicals = [];
        foreach (['/tw/play/'.$board->id, '/tw/play/share/'.$board->share_code] as $path) {
            $html = $this->asAgeVerified()->get($path)->assertOk()->getContent();
            preg_match('#<link rel="canonical" href="([^"]*)"#', $html, $m);
            $canonicals[$path] = $m[1] ?? null;
        }

        $this->assertSame(
            ...array_values($canonicals),
        );
        $this->assertStringContainsString($board->share_code, (string) reset($canonicals));
    }

    public function test_pages_only_translated_in_one_locale_do_not_hreflang_the_others(): void
    {
        /* hreflang 指向一個 noindex 的頁面是自相矛盾的訊號,Google 會整組忽略。
           屬性測驗與性壓抑指數測驗只有繁中有文案,所以它們只該宣告繁中。 */
        foreach (['/en/trait-test', '/en/dual-control'] as $path) {
            $head = $this->asAgeVerified()->get($path)->assertOk()->getContent();
            $head = substr($head, 0, (int) strpos($head, '</head>'));

            preg_match_all('#<link rel="alternate" hreflang="([^"]+)"#', $head, $m);

            $this->assertSame(
                ['zh-TW', 'x-default'],
                $m[1],
                "{$path} 宣告了它自己並沒有翻譯的語系"
            );
        }
    }

    public function test_every_quadrant_page_links_to_the_other_four(): void
    {
        /* 母頁不列象限了,所以互連就是它們唯一的站內發現路徑。少了這個,五頁只剩
           sitemap 一條路 —— 而 sitemap 是「請你來看」,內鏈才是「這幾頁有關係」。 */
        $slugs = array_column((array) trans('horny.quadrants', [], 'zh_TW'), 'slug');
        $this->assertCount(5, $slugs);

        foreach ($slugs as $slug) {
            $html = $this->asAgeVerified()->get("/tw/dual-control/{$slug}")->assertOk()->getContent();

            foreach ($slugs as $other) {
                $this->assertStringContainsString(
                    "/tw/dual-control/{$other}",
                    $html,
                    "象限頁 {$slug} 沒有連到 {$other}",
                );
            }
        }
    }

    public function test_every_trait_page_links_to_the_other_nineteen(): void
    {
        /* 兩份測驗的母頁都**刻意不列出**結果頁(做完才知道自己是哪一種),所以結果頁
           之間的互連就是它們唯一的站內發現路徑 —— sitemap 是「請你來看」,內鏈才是
           「這幾頁有關係」。原本這裡守的是「母頁要連到所有結果頁」,母頁不列了之後
           那條變成一個空迴圈(靜靜通過),所以整條換成守互連。

           只抽查三型:20 型 × 20 個字串比對要跑 400 次,而互連是同一段迴圈印出來的,
           抽查抓得到「那段迴圈壞了」,那才是會發生的故障。 */
        $slugs = array_column((array) trans('traits.items', [], 'zh_TW'), 'slug');
        $this->assertCount(20, $slugs);

        foreach (array_slice($slugs, 0, 3) as $slug) {
            $html = $this->asAgeVerified()->get("/tw/trait-test/{$slug}")->assertOk()->getContent();

            foreach ($slugs as $other) {
                $this->assertStringContainsString(
                    "/tw/trait-test/{$other}",
                    $html,
                    "屬性頁 {$slug} 沒有連到 {$other}",
                );
            }
        }
    }

    public function test_the_sitemap_index_lists_every_ready_locale(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (LocaleHelper::readyLocales() as $meta) {
            $this->assertStringContainsString('sitemap-'.$meta['prefix'].'.xml', $xml);
        }
    }

    public function test_every_sitemap_url_carries_a_lastmod(): void
    {
        /* 沒有 lastmod 的 sitemap,Google 只能靠自己猜什麼時候該回來重抓。
           這個站的內容住在語系檔裡,檔案 mtime 就是誠實的答案 —— 但**不能**
           每次都塞 now():每一頁都宣稱剛改過的話,這個欄位會整個被忽略。 */
        $xml = $this->get('/sitemap-tw.xml')->assertOk()->getContent();

        $urls = substr_count($xml, '<loc>');
        $stamps = substr_count($xml, '<lastmod>');

        $this->assertGreaterThan(0, $urls);
        $this->assertSame($urls, $stamps, 'sitemap 裡有網址沒有 lastmod');

        // 時間必須是可解析的 ISO 8601,而且不能是未來
        preg_match_all('#<lastmod>([^<]+)</lastmod>#', $xml, $m);
        foreach (array_unique($m[1]) as $value) {
            $parsed = strtotime($value);
            $this->assertNotFalse($parsed, "lastmod 解析不了:{$value}");
            $this->assertLessThanOrEqual(time() + 60, $parsed, "lastmod 是未來時間:{$value}");
        }
    }

    public function test_the_previous_owners_store_urls_are_gone_not_missing(): void
    {
        /* 這個網域的前一手是一間 Shopify 商店,搜尋引擎至今還在抓它的網址。
           404 的意思是「暫時找不到」,所以會一直被重抓;410 才是「永久沒有了」。

           兩種形式都要測:原始網址,以及被我們自己的語系轉址加上前綴之後的
           第二跳 —— 少測後者的話,301 → 404 的鏈子會活得好好的。 */
        foreach ([
            '/products/powerprostick',
            '/collections/all',
            '/sq/collections/all?page=4',
            '/nl/products/musicpunch',
            '/tw/products/powerprostick',
            '/tw/collections/all',
            '/cart',
            '/pages/about-us',
        ] as $url) {
            $this->get($url)->assertStatus(410);
        }
    }

    public function test_real_pages_are_not_caught_by_the_retired_url_rule(): void
    {
        /* 規則寫太寬的話會把自己的頁面也埋掉,而且是靜靜地埋掉。
           這裡挑的是不需要任何資料就能渲染的頁面 —— /play 要有預設棋盤,
           那是別的測試的守備範圍。 */
        $this->get('/tw')->assertOk();
        $this->get('/tw/guide')->assertOk();
        $this->get('/tw/templates')->assertOk();
        $this->get('/tw/community')->assertOk();
        $this->get('/tw/premium')->assertOk();
    }
}
