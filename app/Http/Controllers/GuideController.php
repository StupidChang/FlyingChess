<?php

namespace App\Http\Controllers;

use App\Support\LocaleHelper;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * 站內文章(玩法指南)。
 *
 *   GET  /{locale}/guide           列表
 *   GET  /{locale}/guide/{slug}    單篇
 *
 * 這一區存在的理由是**擴大關鍵字面積**:遊戲頁吃的是工具型意圖(「我現在要玩」),
 * 這裡吃的是資訊型意圖(「有什麼」「怎麼玩」「怎麼辦」)。兩者不能互相搶字,
 * 詳見 config/guides.php 開頭那段關於 keyword cannibalization 的說明。
 *
 * 內容全部在檔案裡(config 骨架 + lang 文案),沒有資料庫、沒有後台 —— 篇數在
 * 二十篇以內的時候這是最省事的做法,而且進版控、可以 code review。
 */
class GuideController extends Controller
{
    public function index()
    {
        return view('guide.index', [
            'articles' => $this->listing(),
            'translated' => $this->isTranslated(),
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('guides.translated', [])),
        ]);
    }

    public function show(string $slug)
    {
        $meta = config("guides.articles.{$slug}");
        abort_if($meta === null, 404);

        $article = $this->lang("articles.{$slug}");
        abort_if($article === [], 404);

        return view('guide.show', [
            'slug' => $slug,
            'article' => $article,
            'updated' => $meta['updated'] ?? null,
            // 末尾的「站內可以直接玩」。過濾掉不存在的路由名稱 —— 之後若有遊戲頁
            // 改名或下架,文章不該因此整頁 500。
            'related' => $this->related($meta['related'] ?? []),
            // 其他文章的入口。文章之間互連,爬蟲才不用每次都從列表頁進來。
            'others' => array_filter(
                $this->listing(),
                fn ($a) => $a['slug'] !== $slug,
            ),
            'translated' => $this->isTranslated(),
            'hreflangLocales' => LocaleHelper::hreflangSet((array) config('guides.translated', [])),
        ]);
    }

    /**
     * 列表用的精簡資料。順序照 config,把最想被看到的放前面。
     *
     * @return array<int, array{slug:string, h1:string, lead:string, updated:?string}>
     */
    private function listing(): array
    {
        $out = [];
        foreach ((array) config('guides.articles') as $slug => $meta) {
            $article = $this->lang("articles.{$slug}");
            if ($article === []) {
                continue;   // 結構有、文案還沒寫的,列表就先不要出現
            }

            $out[] = [
                'slug' => $slug,
                'h1' => $article['h1'] ?? $slug,
                'lead' => $article['lead'] ?? '',
                'updated' => $meta['updated'] ?? null,
                // 卡片上的圖示沿用第一節的圖示 —— 不必再維護第二份對照表
                'icon' => $article['sections'][0]['icon'] ?? 'dot',
            ];
        }

        return $out;
    }

    /**
     * 把路由名稱換成可以直接渲染的連結。
     *
     * @param  array<int, string>  $names
     * @return array<int, array{url:string, label:string}>
     */
    private function related(array $names): array
    {
        $labels = [
            'games.lobby' => 'games.flying_chess',
            'truth-dare.lobby' => 'games.truth_dare',
            'card-game.show' => 'minigame.card_title',
            'who-most-likely.show' => 'minigame.wml_title',
            'trait-test.show' => 'traits.title',
            'horny-test.show' => 'horny.title',
            'play' => 'ui.play',
        ];

        $out = [];
        foreach ($names as $name) {
            if (! RouteFacade::has($name) || ! isset($labels[$name])) {
                continue;
            }
            $out[] = ['url' => route($name), 'label' => trans($labels[$name])];
        }

        return $out;
    }

    private function isTranslated(): bool
    {
        return in_array(app()->getLocale(), (array) config('guides.translated', []), true);
    }

    /**
     * 讀文案。沒翻譯的語系一律退回預設語系,而不是讓畫面出現一串 key。
     * 和 TraitTestService / RepressionTestService 同一個做法。
     */
    private function lang(string $key): array
    {
        $locale = $this->isTranslated() ? app()->getLocale() : LocaleHelper::defaultLocale();

        return (array) trans("guides.{$key}", [], $locale);
    }
}
