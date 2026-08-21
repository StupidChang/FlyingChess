<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 站內文章(玩法指南)。
 *
 * 這裡守三件會靜靜壞掉的事:
 *
 *   1. config 與 lang 走鐘。文章的骨架在 config、文案在 lang,兩邊用 slug 對起來 ——
 *      config 加了一篇卻忘記寫文案,列表頁只會默默少一項,不會報錯。
 *   2. **粗體** 的標記漏出來。文案裡的 ** 要經過 inline_emphasis 才會變成 <strong>,
 *      漏掉某個欄位的話頁面上就是一堆裸星號 —— 不會壞,只是很醜而且看起來像壞了。
 *   3. FAQ schema 宣告了頁面上沒有的問答。那是結構化資料的政策違規,而兩半都讀
 *      同一個 lang 檔就是為了讓它不可能發生 —— 這條負責證明那個假設還成立。
 */
class GuideTest extends TestCase
{
    use RefreshDatabase;

    private function visit(string $url)
    {
        return $this->asAgeVerified()->get($url);
    }

    /** @return array<int, string> */
    private function slugs(): array
    {
        return array_keys((array) config('guides.articles'));
    }

    public function test_every_configured_article_has_its_copy_written(): void
    {
        foreach ($this->slugs() as $slug) {
            $article = (array) trans("guides.articles.{$slug}", [], 'zh_TW');

            foreach (['h1', 'lead', 'seo_title', 'seo_description', 'sections'] as $key) {
                $this->assertNotEmpty($article[$key] ?? null, "文章 {$slug} 缺少 {$key}");
            }

            // 每個段落至少要有標題與一段內文,不然目錄會出現空項目
            foreach ($article['sections'] as $i => $section) {
                $this->assertNotEmpty($section['h2'] ?? null, "{$slug} 第 {$i} 段缺標題");
                $this->assertNotEmpty(
                    ($section['p'] ?? []) + ($section['ul'] ?? []),
                    "{$slug} 第 {$i} 段沒有任何內容"
                );
            }
        }
    }

    public function test_the_index_lists_every_article(): void
    {
        $html = $this->visit('/tw/guide')->assertOk()->getContent();

        foreach ($this->slugs() as $slug) {
            $this->assertStringContainsString("/tw/guide/{$slug}", $html, "列表頁沒有連到 {$slug}");
        }
    }

    public function test_every_article_page_renders(): void
    {
        foreach ($this->slugs() as $slug) {
            $article = (array) trans("guides.articles.{$slug}", [], 'zh_TW');

            /* 導言與段落會經過 inline_emphasis(**粗體** → <strong>),所以要拿
               轉換後的字串比對 —— 直接比原文的話,只要文案裡出現一個粗體就會失敗。 */
            $this->visit("/tw/guide/{$slug}")
                ->assertOk()
                ->assertSee($article['h1'])
                ->assertSee(article_text($article['lead']), false);
        }
    }

    public function test_unknown_slug_is_404(): void
    {
        $this->visit('/tw/guide/not-an-article')->assertNotFound();
    }

    public function test_no_raw_bold_markers_leak_into_the_page(): void
    {
        /* 列表頁也要檢查:它印的是每篇的導言,而導言裡有粗體 —— 忘了轉換的話
           卡片上會出現一排星號,而文章頁看起來完全正常。 */
        $indexHtml = $this->visit('/tw/guide')->assertOk()->getContent();
        $this->assertStringNotContainsString('**', $indexHtml, '列表頁有沒被處理的 ** 標記');
        $this->assertStringNotContainsString('`', $indexHtml, '列表頁有沒被處理的 ` 標記');

        foreach ($this->slugs() as $slug) {
            $html = $this->visit("/tw/guide/{$slug}")->assertOk()->getContent();

            $this->assertStringNotContainsString('**', $html, "{$slug} 的頁面上有沒被處理的 ** 標記");

            /* 反引號與波浪號同理。文案裡寫了 `句子` 但 view 忘了走 article_text()
               的話,頁面上會出現一排反引號,而且不會有任何錯誤。 */
            $this->assertStringNotContainsString('`', $html, "{$slug} 的頁面上有沒被處理的 ` 標記");
            $this->assertStringNotContainsString('~~', $html, "{$slug} 的頁面上有沒被處理的 ~~ 標記");
            $this->assertStringNotContainsString('[[', $html, "{$slug} 的頁面上有沒被處理的 [[ 連結");
        }
    }

    public function test_inline_emphasis_cannot_emit_anything_but_strong(): void
    {
        /* 這個 helper 會輸出未轉義的 HTML({!! !!}),所以它是唯一一個「文案內容
           變成標籤」的路徑。先 escape 再替換的順序如果哪天被寫反,這條會叫。 */
        $out = inline_emphasis('**粗**<script>alert(1)</script><b>x</b>');

        $this->assertStringContainsString('<strong>粗</strong>', $out);
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringNotContainsString('<b>', $out);
    }

    public function test_faq_schema_matches_what_the_page_shows(): void
    {
        foreach ($this->slugs() as $slug) {
            $html = $this->visit("/tw/guide/{$slug}")->assertOk()->getContent();

            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
            $this->assertNotEmpty($m[1], "{$slug} 沒有任何 JSON-LD");

            $faq = null;
            foreach ($m[1] as $block) {
                $decoded = json_decode($block, true);
                // 少一個逗號就會讓整塊對所有消費者隱形,而頁面照樣顯示正常
                $this->assertNotNull($decoded, "{$slug} 的 JSON-LD 壞了: ".json_last_error_msg());

                foreach (is_array($decoded) && isset($decoded[0]) ? $decoded : [$decoded] as $node) {
                    if (($node['@type'] ?? null) === 'FAQPage') {
                        $faq = $node;
                    }
                }
            }

            $this->assertNotNull($faq, "{$slug} 沒有 FAQPage");

            foreach ($faq['mainEntity'] as $q) {
                $this->assertStringContainsString(
                    e($q['name']),
                    $html,
                    "{$slug} 的 schema 宣告了頁面上看不到的問題"
                );
            }
        }
    }

    public function test_articles_do_not_reuse_a_title_from_elsewhere_on_the_site(): void
    {
        /* 文章吃資訊型意圖,遊戲頁吃工具型 —— 兩者標題撞在一起就是自己跟自己搶
           排名(keyword cannibalization)。這條只抓最明顯的情況:一模一樣的 title。 */
        $titles = [];
        foreach (array_merge(['/tw/guide'], array_map(fn ($s) => "/tw/guide/{$s}", $this->slugs())) as $path) {
            $html = $this->visit($path)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#s', $html, $m);
            $titles[$path] = trim($m[1] ?? '');
        }

        foreach (['/tw', '/tw/game-hall', '/tw/truth-dare', '/tw/trait-test', '/tw/repression-test'] as $path) {
            $html = $this->visit($path)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#s', $html, $m);
            $titles[$path] = trim($m[1] ?? '');
        }

        $this->assertSame(
            count($titles),
            count(array_unique($titles)),
            '有頁面共用同一個 <title>: '.json_encode(
                array_diff_assoc($titles, array_unique($titles)),
                JSON_UNESCAPED_UNICODE
            )
        );
    }
}
