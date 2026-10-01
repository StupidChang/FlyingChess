<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 文章圖示(partials/article-icon)。
 *
 * 找不到名字的時候那個 partial 會**靜靜**退回一個小圓點 —— 打錯字不報錯、不破版,
 * 只會讓那一個區塊看起來很敷衍,而且沒有人會注意到。所以名字對不對只能在這裡擋。
 */
class ArticleIconTest extends TestCase
{
    /**
     * partial 裡定義了哪些圖示名。
     *
     * @return array<int,string>
     */
    private function defined(): array
    {
        $src = (string) file_get_contents(resource_path('views/partials/article-icon.blade.php'));
        preg_match_all("/^\s*'([a-z-]+)' => '/m", $src, $m);

        return $m[1];
    }

    public function test_every_icon_the_guides_ask_for_actually_exists(): void
    {
        $defined = $this->defined();
        $this->assertContains('dot', $defined, '後備用的小圓點不見了,打錯字會變成完全沒有圖示');

        foreach ((array) __('guides.articles') as $slug => $article) {
            if (isset($article['icon'])) {
                $this->assertContains($article['icon'], $defined, "{$slug} 的文章圖示「{$article['icon']}」不存在");
            }

            foreach ((array) ($article['sections'] ?? []) as $i => $section) {
                if (! isset($section['icon'])) {
                    continue;
                }
                $this->assertContains($section['icon'], $defined, "{$slug} 第 {$i} 節的圖示「{$section['icon']}」不存在");
            }
        }
    }

    public function test_the_trait_result_sections_each_get_their_own_real_icon(): void
    {
        $blade = (string) file_get_contents(resource_path('views/trait-test/result.blade.php'));
        preg_match_all("/section-head', \['icon' => '([a-z-]+)'/", $blade, $m);
        $icons = $m[1];

        $this->assertCount(11, $icons, '結果頁的區塊數變了 —— 新區塊忘記配圖示,或是有區塊被刪掉');

        foreach ($icons as $icon) {
            $this->assertContains($icon, $this->defined(), "結果頁用了不存在的圖示「{$icon}」");
        }

        // 兩個區塊共用一個圖示等於沒有圖示 —— 眼睛沒有落點可以分辨
        $this->assertSame($icons, array_unique($icons), '有兩個區塊用了同一個圖示');
    }
}
