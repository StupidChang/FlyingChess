<?php

namespace Tests\Feature;

use App\Console\Commands\ContentTranslationCoverage;
use App\Http\Middleware\AgeVerification;
use App\Models\Board;
use App\Models\BoardSquare;
use App\Models\GamePrompt;
use App\Models\TruthDareCard;
use App\Support\ContentTranslations;
use App\Support\LocaleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 內建內容(範本棋盤、題卡、轉盤、小遊戲題庫)的翻譯字典。
 *
 * 字典以繁中原文當 key。這裡守三件事:seed 出來的內容每一句都翻到了、翻譯沒把
 * 佔位符／換行／數字弄丟、沒翻完的棋盤頁面不會被收錄。
 */
class ContentTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALES = ['en', 'zh_CN', 'ja'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    /** 字典裡任意一句,拿來當測試用的原文 */
    private function knownSource(): string
    {
        return array_key_first(ContentTranslations::dictionary('en'));
    }

    public function test_every_seeded_sentence_has_a_translation(): void
    {
        $this->seed();

        $missing = [];
        foreach (self::LOCALES as $locale) {
            foreach (ContentTranslationCoverage::sources() as $kind => $texts) {
                foreach ($texts as $text) {
                    if (ContentTranslations::lookup($text, $locale) === null) {
                        $missing[] = "{$locale}/{$kind}: ".mb_substr($text, 0, 40);
                    }
                }
            }
        }

        // 改了 seeder 或 Service 的預設題庫,這裡就會列出對不上的句子:
        // 把新的原文與譯文加進 resources/content-translations/*.php
        $this->assertSame([], array_slice($missing, 0, 30), count($missing).' 句沒有翻譯');
    }

    public function test_the_three_dictionaries_translate_the_same_sentences(): void
    {
        $base = array_keys(ContentTranslations::dictionary('en'));
        sort($base);

        foreach (['zh_CN', 'ja'] as $locale) {
            $keys = array_keys(ContentTranslations::dictionary($locale));
            sort($keys);
            $this->assertSame($base, $keys, $locale);
        }
    }

    public function test_translations_keep_placeholders_line_breaks_and_numbers(): void
    {
        $problems = [];

        foreach (self::LOCALES as $locale) {
            foreach (ContentTranslations::dictionary($locale) as $src => $dst) {
                $tag = "{$locale}: ".mb_substr(str_replace("\n", '⏎', $src), 0, 30);

                // {A}/{B} 是玩家名字,少一個遊戲就會印出「{A} 親」
                foreach (['{A}', '{B}'] as $ph) {
                    if (substr_count($src, $ph) !== substr_count($dst, $ph)) {
                        $problems[] = "{$tag} 佔位符 {$ph}";
                    }
                }
                // 格子裡的換行是排版:標題一行、指令一行
                if (substr_count($src, "\n") !== substr_count($dst, "\n")) {
                    $problems[] = "{$tag} 換行數";
                }
                // 秒數、格數、杯數翻丟了,玩法就變了
                preg_match_all('/\d+/', $src, $nums);
                foreach (array_unique($nums[0]) as $n) {
                    if (! str_contains($dst, $n)) {
                        $problems[] = "{$tag} 數字 {$n}";
                    }
                }
            }
        }

        foreach (ContentTranslations::dictionary('en') as $src => $dst) {
            if (preg_match('/\p{Han}/u', $dst)) {
                $problems[] = 'en 還有中文: '.mb_substr($dst, 0, 30);
            }
        }

        $this->assertSame([], array_slice($problems, 0, 30), count($problems).' 個問題');
    }

    public function test_the_admin_column_wins_then_the_dictionary_then_the_original(): void
    {
        $src = $this->knownSource();
        $en = ContentTranslations::dictionary('en')[$src];

        // 繁中永遠是原文
        $this->assertSame($src, LocaleHelper::pickTranslation(null, $src, 'zh_TW'));
        // 欄位沒填 → 字典
        $this->assertSame($en, LocaleHelper::pickTranslation(null, $src, 'en'));
        // 後台在欄位裡手動填過 → 以欄位為準
        $this->assertSame('hand-written', LocaleHelper::pickTranslation(['en' => 'hand-written'], $src, 'en'));
        // 字典裡沒有的(使用者自己寫的) → 原文
        $this->assertSame('我自己寫的格子', LocaleHelper::pickTranslation(null, '我自己寫的格子', 'en'));
        // \r\n 與前後空白不影響對照
        $this->assertSame($en, ContentTranslations::translate(' '.str_replace("\n", "\r\n", $src).' ', 'en'));
    }

    public function test_models_read_the_dictionary_on_foreign_pages(): void
    {
        $src = $this->knownSource();
        $card = TruthDareCard::create(['category' => 'truth', 'content' => $src, 'level' => 1]);

        app()->setLocale('ja');
        $this->assertSame(ContentTranslations::dictionary('ja')[$src], $card->fresh()->content);

        app()->setLocale('zh_TW');
        $this->assertSame($src, $card->fresh()->content);
    }

    public function test_mini_game_pools_come_out_translated(): void
    {
        $src = $this->knownSource();
        GamePrompt::create(['game' => 'king_game', 'pool' => 'mild', 'content' => $src, 'sort_order' => 0]);
        GamePrompt::create(['game' => 'king_game', 'pool' => 'mild', 'content' => '後台自己加的題目', 'sort_order' => 1]);

        app()->setLocale('en');
        $pools = GamePrompt::poolsFor('king_game');

        $this->assertSame([ContentTranslations::dictionary('en')[$src], '後台自己加的題目'], $pools['mild']);
    }

    private function boardWith(array $texts): Board
    {
        $board = Board::create([
            'name' => $this->knownSource(), 'is_template' => true, 'share_code' => 'TRANSL01',
            'canvas_rows' => 3, 'canvas_cols' => 3,
        ]);
        foreach (array_values($texts) as $i => $text) {
            BoardSquare::create(['board_id' => $board->id, 'position' => $i, 'text' => $text, 'color' => 'normal', 'grid_row' => 0, 'grid_col' => $i]);
        }

        return $board->fresh('squares');
    }

    public function test_a_fully_translated_board_is_indexed_in_that_language(): void
    {
        // 挑像格子的句子:短、沒有引號(引號在 HTML 裡會被跳脫,比對起來不直觀)
        $sources = array_slice(array_values(array_filter(
            array_keys(ContentTranslations::dictionary('en')),
            fn ($t) => mb_strlen($t) < 20 && ! preg_match('/["\'「」\n]/u', $t.ContentTranslations::dictionary('en')[$t]),
        )), 0, 3);
        $board = $this->boardWith([...$sources, '🎲', 'P1']);

        $this->assertSame(['zh_TW', 'en', 'zh_CN', 'ja'], array_values(array_intersect(['zh_TW', 'en', 'zh_CN', 'ja'], $board->translatedLocales())));

        $html = $this->get('/en/play/share/TRANSL01')->assertOk()->getContent();
        $this->assertStringContainsString('content="index,follow"', $html);
        $this->assertStringContainsString(e(ContentTranslations::dictionary('en')[$sources[0]]), $html);
    }

    public function test_a_board_with_an_untranslated_square_stays_out_of_that_index(): void
    {
        $this->boardWith([array_keys(ContentTranslations::dictionary('en'))[1], '這一格沒有人翻過']);

        // 繁中照常收錄,英文頁退回中文顯示、不收錄,也不進英文 sitemap
        $this->get('/tw/play/share/TRANSL01')->assertOk()->assertSee('content="index,follow"', false);
        $this->get('/en/play/share/TRANSL01')->assertOk()->assertSee('content="noindex,follow"', false);

        $this->get('/sitemap-tw.xml')->assertOk()->assertSee('/tw/play/share/TRANSL01', false);
        $this->get('/sitemap-en.xml')->assertOk()->assertDontSee('/play/share/TRANSL01', false);
    }

    public function test_the_stub_translator_refuses_to_write_chinese_as_a_translation(): void
    {
        $this->artisan('translate:auto')->assertFailed();
    }

    public function test_card_game_roles_can_be_found_in_every_translation(): void
    {
        // cards/show 用 minigame.card_role_high/low 在題目裡找「牌大的人」換成玩家名字。
        // 翻譯要是換了一種說法,名字就換不進去,畫面上會留著「Higher card」。
        foreach (['zh_TW', ...self::LOCALES] as $locale) {
            $high = '/'.trans('minigame.card_role_high', [], $locale).'/iu';
            $low = '/'.trans('minigame.card_role_low', [], $locale).'/iu';

            foreach (array_keys(ContentTranslations::dictionary('en')) as $src) {
                $text = ContentTranslations::translate($src, $locale);
                if (str_contains($src, '牌大的')) {
                    $this->assertMatchesRegularExpression($high, $text, "{$locale}: {$text}");
                }
                if (str_contains($src, '牌小的')) {
                    $this->assertMatchesRegularExpression($low, $text, "{$locale}: {$text}");
                }
            }
        }

        // 繁中原文「牌大的人」整段要被換掉,不能留下一個「人」
        $this->assertSame('小明靠過去', preg_replace('/'.trans('minigame.card_role_high', [], 'zh_TW').'/u', '小明', '牌大的人靠過去'));
    }

    public function test_share_previews_no_longer_carry_the_old_name(): void
    {
        foreach (['/tw', '/en/who-most-likely', '/jp/wheel-game'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('content="情侶飛行棋"', false);
        }
    }

    public function test_the_gender_choices_follow_the_language(): void
    {
        $this->get('/en/truth-dare')->assertOk()->assertSee('<option value="male">Male</option>', false);
        $this->get('/tw/truth-dare')->assertOk()->assertSee('<option value="male">男</option>', false);
    }
}
