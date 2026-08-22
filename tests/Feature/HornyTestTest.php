<?php

namespace Tests\Feature;

use App\Services\HornyTestService;
use Tests\TestCase;

/**
 * 色度測驗(色度 × 煞車 兩條軸)。
 *
 * 這裡守的是幾件會靜靜壞掉、而且壞了沒人會發現的事:
 *
 *   1. 反向題。config 的 dir 一旦寫錯,分數還是算得出來、頁面還是會顯示,
 *      只是全部的人都被放到錯的象限 —— 沒有測試就沒有任何跡象。
 *   2. 面向要掛在正確的軸上。掛錯的話兩條軸都算得出數字,只是意義是混的。
 *   3. 四個角落與中央那一塊的判定。
 *   4. 鎖住的深入解讀**不能**出現在 HTML 裡。用 CSS 遮起來等於沒鎖。
 *
 * 請求都當成「已確認年齡的訪客」:AgeVerification 在 web 群組裡,比路由中介層更早
 * 跑。見 TestCase::asAgeVerified 與 AgeGateTest。
 */
class HornyTestTest extends TestCase
{
    private function visit(string $url)
    {
        return $this->asAgeVerified()->get($url);
    }

    /**
     * 造一份答案卷。
     *
     * @param  string  $desire  'high' | 'low' | 'mid'
     * @param  string  $brake  'high' | 'low' | 'mid'
     * @return array<int, int>
     */
    private function answers(string $desire, string $brake): array
    {
        $want = ['desire' => $desire, 'brake' => $brake];
        $out = [];

        foreach (config('horny.questions') as $i => $q) {
            $axis = config("horny.dimensions.{$q['dim']}.axis");
            $target = $want[$axis] ?? 'mid';

            if ($target === 'mid') {
                $out[$i] = 2;

                continue;
            }

            $high = ($q['dir'] ?? 1) > 0 ? HornyTestService::MAX : HornyTestService::MIN;
            $low = ($q['dir'] ?? 1) > 0 ? HornyTestService::MIN : HornyTestService::MAX;
            $out[$i] = $target === 'high' ? $high : $low;
        }

        return $out;
    }

    /**
     * 逐**面向**指定的答案卷。整條軸一起拉滿的話每個面向都一樣高,
     * 「煞車集中在哪一條」這種重點就永遠測不到。
     *
     * @param  array<string, string>  $targets  面向 => 'high' | 'low' | 'mid'
     * @return array<int, int>
     */
    private function answersByDim(array $targets, string $default = 'mid'): array
    {
        $out = [];

        foreach (config('horny.questions') as $i => $q) {
            $target = $targets[$q['dim']] ?? $default;

            if ($target === 'mid') {
                $out[$i] = 2;

                continue;
            }

            $high = ($q['dir'] ?? 1) > 0 ? HornyTestService::MAX : HornyTestService::MIN;
            $out[$i] = $target === 'high' ? $high : ($high === HornyTestService::MAX ? HornyTestService::MIN : HornyTestService::MAX);
        }

        return $out;
    }

    public function test_question_text_and_structure_stay_aligned(): void
    {
        // 第 N 句題目配第 N 個結構。錯位不會報錯,只會讓每個人被算錯面向。
        $this->assertCount(
            count(config('horny.questions')),
            (array) trans('horny.questions', [], 'zh_TW'),
        );
    }

    public function test_every_dimension_has_equal_normal_and_reverse_items(): void
    {
        /* 只有正向題的面向,測到的是「這個人會不會一路按到底」,不是他的狀態。
           而且數量要**對稱**:6 題裡 4:2 的話,每題都按「完全符合」的人基線就會
           偏掉,那條軸默默偏高。加題時很容易破壞這個對稱,所以這裡守住。 */
        $dirs = [];
        foreach (config('horny.questions') as $q) {
            $dirs[$q['dim']][$q['dir']] = ($dirs[$q['dim']][$q['dir']] ?? 0) + 1;
        }

        foreach (array_keys(config('horny.dimensions')) as $dim) {
            $this->assertSame(
                $dirs[$dim][1] ?? 0,
                $dirs[$dim][-1] ?? 0,
                "面向 {$dim} 的正向題與反向題數量不對稱",
            );
        }
    }

    public function test_every_dimension_belongs_to_a_declared_axis(): void
    {
        /* 面向掛錯軸(或掛到不存在的軸)的話,兩條軸都還是算得出數字,只是意義是
           混的 —— 象限會亂,但畫面上完全看不出來。 */
        $axes = array_keys((array) config('horny.axes'));

        foreach (config('horny.dimensions') as $key => $dim) {
            $this->assertContains($dim['axis'] ?? null, $axes, "面向 {$key} 沒有掛在任何一條軸上");
        }

        // 反過來也要對:軸宣告的 dims 必須真的存在
        foreach (config('horny.axes') as $axis => $meta) {
            foreach ($meta['dims'] ?? [] as $dim) {
                $this->assertSame($axis, config("horny.dimensions.{$dim}.axis"), "軸 {$axis} 宣告的 {$dim} 對不上");
            }
        }
    }

    public function test_each_corner_of_the_map_resolves_to_its_own_quadrant(): void
    {
        $service = app(HornyTestService::class);

        foreach ([
            ['high', 'high', 'simmering', 100, 100],
            ['high', 'low', 'open', 100, 0],
            ['low', 'low', 'easy', 0, 0],
            ['low', 'high', 'locked', 0, 100],
        ] as [$desire, $brake, $expected, $desirePct, $brakePct]) {
            $r = $service->score($this->answers($desire, $brake));

            $this->assertSame($desirePct, $r['axes']['desire'], "色度 {$desire} 算錯");
            $this->assertSame($brakePct, $r['axes']['brake'], "煞車 {$brake} 算錯");
            $this->assertSame($expected, $r['quadrant']);
        }
    }

    public function test_answering_everything_the_same_lands_in_the_middle(): void
    {
        /* 每題都選「完全符合」的人,反向題會把分數抵銷掉 —— 兩條軸都是 50,
           落在中央那一塊。這正是混入反向題的目的,壞掉的話這條會先叫。 */
        $service = app(HornyTestService::class);
        $answers = array_fill(0, count(config('horny.questions')), HornyTestService::MAX);
        $r = $service->score($answers);

        $this->assertSame(50, $r['axes']['desire']);
        $this->assertSame(50, $r['axes']['brake']);
        $this->assertSame('middle', $r['quadrant']);
    }

    public function test_the_middle_band_is_a_square_around_fifty(): void
    {
        $service = app(HornyTestService::class);
        $band = $service->middleBand();

        // 兩條軸都在 50±band 之內 → 中央
        $this->assertSame('middle', $service->quadrantFor(50 + $band, 50 - $band));
        // 只要有一條軸出界,就回到四個角
        $this->assertSame('simmering', $service->quadrantFor(50 + $band + 1, 50));
        $this->assertSame('locked', $service->quadrantFor(50 - $band - 1, 50));
    }

    public function test_every_quadrant_page_renders(): void
    {
        foreach (app(HornyTestService::class)->quadrants() as $q) {
            $this->visit('/tw/dual-control/'.$q['slug'])
                ->assertOk()
                ->assertSee($q['name'])
                ->assertDontSee('class="age-gate"', false);
        }
    }

    public function test_the_question_page_renders(): void
    {
        $this->visit('/tw/dual-control')
            ->assertOk()
            ->assertSee(__('horny.h1'))
            // 兩條軸的說明是這一頁對搜尋引擎唯一說得出「在測什麼」的內容
            ->assertSee(__('horny.axes.desire.name'))
            ->assertSee(__('horny.axes.brake.name'));
    }

    public function test_unknown_quadrant_slug_is_404(): void
    {
        $this->visit('/tw/dual-control/not-a-quadrant')->assertNotFound();
    }

    public function test_submitting_redirects_to_the_matching_quadrant(): void
    {
        $this->asAgeVerified()
            ->post('/tw/dual-control', ['a' => $this->answers('high', 'high')])
            ->assertRedirect('/tw/dual-control/simmering');
    }

    public function test_incomplete_submission_is_rejected(): void
    {
        $answers = $this->answers('high', 'high');
        unset($answers[0]);

        $this->asAgeVerified()
            ->post('/tw/dual-control', ['a' => $answers])
            ->assertSessionHasErrors('a');
    }

    public function test_locked_deep_reading_is_not_rendered_into_the_html(): void
    {
        /* 鎖住的內容塞進 HTML 再用 CSS 遮起來,等於檢視原始碼就破解了。
           訪客(未解鎖)拿到的頁面裡不該出現那段建議的任何一個字。 */
        $quad = trans('horny.quadrants.simmering', [], 'zh_TW');

        $response = $this->visit('/tw/dual-control/simmering')->assertOk();

        $response->assertDontSee($quad['advice']);
        $response->assertDontSee($quad['partner']);
        foreach ($quad['steps'] as $step) {
            $response->assertDontSee($step);
        }
    }

    public function test_watching_an_ad_unlocks_the_deep_reading(): void
    {
        /* 反過來也要測:鎖住時不洩漏之外,解鎖後必須真的顯示。少了這條,付費區的
           欄位名打錯字會靜靜地什麼都不渲染,而畫面上看不出差別。 */
        $quad = trans('horny.quadrants.open', [], 'zh_TW');

        $token = $this->asAgeVerified()->postJson('/tw/ad-unlock/start')->json('token');
        $this->travel(config('premium.rewarded.min_watch_seconds', 15) + 1)->seconds();
        $this->asAgeVerified()->postJson('/tw/ad-unlock/claim', ['token' => $token])
            ->assertJsonPath('ok', true);

        $response = $this->visit('/tw/dual-control/open')->assertOk();

        $response->assertSee($quad['advice'])->assertSee($quad['partner']);
        foreach ($quad['steps'] as $step) {
            $response->assertSee($step);
        }
        $response->assertDontSee(__('horny.result.deep_locked'));
    }

    public function test_a_visitor_without_a_score_still_gets_real_content(): void
    {
        /* 從搜尋或分享連結進來的人沒有分數。這幾段就是他讀到的全部,少了就等於
           五個象限頁對搜尋引擎幾乎是同一頁。 */
        $quad = trans('horny.quadrants.locked', [], 'zh_TW');

        $response = $this->visit('/tw/dual-control/locked')->assertOk();

        foreach ($quad['signals'] as $signal) {
            $response->assertSee($signal);
        }
        $response->assertSee($quad['bedroom']);
        foreach ($quad['stuck'] as $item) {
            $response->assertSee($item);
        }
        $response->assertSee($quad['misread']);

        // 免費變厚不等於把付費那半送出去
        $response->assertDontSee($quad['advice']);
    }

    public function test_every_quadrant_has_the_free_and_paid_content_filled_in(): void
    {
        // 少一格就是少一頁內容,而那一頁照樣會被收錄
        foreach (trans('horny.quadrants', [], 'zh_TW') as $key => $quad) {
            $this->assertCount(4, $quad['signals'] ?? [], "{$key} 的典型表現不是四條");
            $this->assertCount(3, $quad['steps'] ?? [], "{$key} 的具體做法不是三條");
            $this->assertCount(4, $quad['stuck'] ?? [], "{$key} 的卡點不是四條");
            foreach (['name', 'slug', 'label', 'line', 'long', 'bedroom', 'misread', 'advice', 'partner'] as $field) {
                $this->assertNotEmpty($quad[$field] ?? null, "{$key} 少了 {$field}");
            }
        }
    }

    public function test_every_dimension_has_all_three_readings(): void
    {
        /* 個人化解讀是照實際分數查表的。少一段不會報錯,只會讓那一條面向在結果頁
           上變成空白 —— 而那一格剛好是分數最高的人才看得到。 */
        $reading = (array) trans('horny.dimension_reading', [], 'zh_TW');

        foreach (array_keys((array) config('horny.dimensions')) as $dim) {
            foreach (['low', 'mid', 'high'] as $level) {
                $this->assertNotEmpty($reading[$dim][$level] ?? null, "面向 {$dim} 少了 {$level} 的解讀");
            }
        }
    }

    public function test_the_desire_level_boundaries_are_inclusive_of_their_lower_bound(): void
    {
        $service = app(HornyTestService::class);

        $this->assertSame('pure', $service->desireLevel(0)['key']);
        $this->assertSame('pure', $service->desireLevel(19)['key']);
        $this->assertSame('mild', $service->desireLevel(20)['key']);
        $this->assertSame('standard', $service->desireLevel(40)['key']);
        $this->assertSame('very', $service->desireLevel(60)['key']);
        $this->assertSame('extreme', $service->desireLevel(80)['key']);
        $this->assertSame('extreme', $service->desireLevel(100)['key']);
    }

    public function test_every_desire_level_has_a_verdict(): void
    {
        // 少一句判定不會報錯,只會讓結果頁最上面那一塊變成空白
        foreach (array_keys((array) config('horny.desire_levels')) as $key) {
            $level = app(HornyTestService::class)->desireLevel(
                (int) config("horny.desire_levels.{$key}.min")
            );
            $this->assertNotEmpty($level['name'], "色度級距 {$key} 少了名字");
            $this->assertNotEmpty($level['line'], "色度級距 {$key} 少了判定句");
        }
    }

    public function test_a_very_horny_result_says_so_before_anything_else(): void
    {
        /* 這份測驗的主角是「你有多色」。頁面上先出現的必須是色度的判定,象限是
           第二句話 —— 反過來的話,很色的人讀完只會覺得又被分析了一次。 */
        $result = app(HornyTestService::class)->score($this->answers('high', 'high'));
        $this->assertSame(100, $result['axes']['desire']);

        $html = $this->asAgeVerified()
            ->withSession(['horny_result' => $result])
            ->get('/tw/dual-control/simmering')
            ->assertOk()
            ->assertSee(__('horny.desire_levels.extreme.name'))
            ->assertSee(__('horny.desire_levels.extreme.line'))
            ->assertSee(__('horny.result.verdict_braked'))
            ->getContent();

        /* 判定要排在 h1(象限名)前面。比對的是 h1 那個元素而不是象限的名字本身 ——
           名字在 <title> 與 og:title 裡也出現,那些都在 body 之前。 */
        $this->assertLessThan(
            strpos($html, 'class="tt-name"'),
            strpos($html, __('horny.desire_levels.extreme.name')),
            '色度判定應該出現在象限名字之前',
        );
    }

    public function test_a_low_desire_result_gets_its_own_verdict(): void
    {
        // 色度淡的人不該被套上「你很色」,而且煞車鬆的那半句要跟著換
        $result = app(HornyTestService::class)->score($this->answers('low', 'low'));

        $this->asAgeVerified()
            ->withSession(['horny_result' => $result])
            ->get('/tw/dual-control/easy')
            ->assertOk()
            ->assertSee(__('horny.desire_levels.pure.name'))
            ->assertSee(__('horny.result.verdict_free'))
            ->assertDontSee(__('horny.desire_levels.extreme.name'));
    }

    public function test_the_brake_sentence_follows_the_quadrant_not_a_separate_threshold(): void
    {
        /* 煞車那半句如果自己設門檻,煞車剛好 50 的人會拿到「色度濃 · 煞車緊」的
           標籤配上「而你的煞車在中間」的句子 —— 同一塊裡自己打自己。 */
        $service = app(HornyTestService::class);
        $answers = $this->answers('high', 'mid');   // 煞車全選中間 → 50
        $result = $service->score($answers);

        $this->assertSame(50, $result['axes']['brake']);
        $this->assertSame('simmering', $result['quadrant']);   // 50 算「緊」那一邊

        $this->asAgeVerified()
            ->withSession(['horny_result' => $result])
            ->get('/tw/dual-control/simmering')
            ->assertOk()
            ->assertSee(__('horny.result.verdict_braked'))
            ->assertDontSee(__('horny.result.verdict_mid'));
    }

    public function test_a_visitor_without_a_score_sees_the_quadrant_not_a_verdict(): void
    {
        /* 從搜尋進來的人沒有分數,不能憑空給他一個色度判定 —— 那一頁的主角是
           象限本身。 */
        $this->visit('/tw/dual-control/simmering')
            ->assertOk()
            ->assertDontSee(__('horny.result.crown'))
            ->assertDontSee(__('horny.desire_levels.extreme.line'));
    }

    public function test_the_highlights_are_computed_from_the_persons_own_numbers(): void
    {
        /* 免費結果原本幾乎沒有屬於這個人自己的內容 —— 兩個數字加一條「最明顯的
           一項」,其餘都是「這一格的人通常怎樣」。這一塊必須真的隨分數改變。 */
        $service = app(HornyTestService::class);

        // 油門滿、煞車鬆 → 落差是往油門那邊
        $keys = collect($service->highlights($service->score($this->answers('high', 'low'))))
            ->pluck('key')->all();
        $this->assertContains('gap_desire', $keys);
        $this->assertNotContains('gap_brake', $keys);

        // 反過來
        $keys = collect($service->highlights($service->score($this->answers('low', 'high'))))
            ->pluck('key')->all();
        $this->assertContains('gap_brake', $keys);
        $this->assertNotContains('gap_desire', $keys);

        // 兩條軸都中間 → 落差很小,而且中間選太多要被提出來
        $keys = collect($service->highlights($service->score($this->answers('mid', 'mid'))))
            ->pluck('key')->all();
        $this->assertContains('gap_even', $keys);
        $this->assertContains('answers_neutral', $keys);

        /* 煞車集中在「身體羞恥」、其餘四條放掉 → 要講「動這一條、別的不用管」,
           而不是「全面性的」。整條軸一起拉滿的答案卷測不到這件事。 */
        $keys = collect($service->highlights($service->score($this->answersByDim([
            'shame' => 'high', 'guilt' => 'low', 'anxiety' => 'low', 'avoid' => 'low', 'voice' => 'low',
        ]))))->pluck('key')->all();
        $this->assertContains('brake_focused', $keys);
        $this->assertNotContains('brake_flat', $keys);

        // 幻想很多卻不出手
        $keys = collect($service->highlights($service->score($this->answersByDim([
            'fantasy' => 'high', 'initiate' => 'low',
        ]))))->pluck('key')->all();
        $this->assertContains('shape_head_not_hands', $keys);
    }

    public function test_the_highlights_never_flood_the_page(): void
    {
        // 一牆重點等於沒有重點
        $service = app(HornyTestService::class);

        foreach ([['high', 'high'], ['high', 'low'], ['low', 'high'], ['low', 'low'], ['mid', 'mid']] as [$d, $b]) {
            $this->assertLessThanOrEqual(
                4,
                count($service->highlights($service->score($this->answers($d, $b)))),
                "{$d}/{$b} 的重點超過四條",
            );
        }
    }

    public function test_every_highlight_key_has_a_sentence(): void
    {
        /* key 打錯字的話 __() 會把 key 本身印在畫面上(「gap_desire」),
           而那看起來就像壞掉。所以逐一確認每個 key 都有句子。 */
        $service = app(HornyTestService::class);
        $sentences = (array) trans('horny.result.highlights', [], 'zh_TW');
        $seen = [];

        $sheets = [
            $this->answers('high', 'high'), $this->answers('high', 'low'),
            $this->answers('low', 'high'), $this->answers('low', 'low'),
            $this->answers('mid', 'mid'),
            // 逐面向的極端組合,才觸發得到「集中在哪一條」與油門的四種形狀
            $this->answersByDim(['shame' => 'high', 'guilt' => 'low', 'anxiety' => 'low', 'avoid' => 'low', 'voice' => 'low']),
            $this->answersByDim(['fantasy' => 'high', 'initiate' => 'low']),
            $this->answersByDim(['initiate' => 'high', 'fantasy' => 'low']),
            $this->answersByDim(['drive' => 'high', 'arousal' => 'low']),
            $this->answersByDim(['arousal' => 'high', 'drive' => 'low']),
        ];

        foreach ($sheets as $sheet) {
            foreach ($service->highlights($service->score($sheet)) as $h) {
                $seen[$h['key']] = true;
                $this->assertArrayHasKey($h['key'], $sentences, "重點 {$h['key']} 沒有對應的句子");
            }
        }

        // 反過來:lang 檔裡不該躺著永遠不會被用到的句子
        foreach (array_keys($sentences) as $key) {
            $this->assertArrayHasKey($key, $seen, "句子 {$key} 在任何一種作答下都沒被用到");
        }
    }

    public function test_the_neutral_answer_counter_actually_counts(): void
    {
        /* (float) $a === $mid 會因為型別不同永遠不成立(MAX+MIN 是整數除法),
           所以「你有幾題選中間」一直是 0 —— 而那句話正是用來告訴人「這份結果偏
           保守」的。性壓抑測驗那一份也踩同一個坑。 */
        $service = app(HornyTestService::class);
        $total = count(config('horny.questions'));

        $allMid = $service->score(array_fill(0, $total, 2));
        $this->assertSame($total, $allMid['meta']['neutral']);
        $this->assertSame(0, $allMid['meta']['decisive']);

        $allMax = $service->score(array_fill(0, $total, HornyTestService::MAX));
        $this->assertSame(0, $allMax['meta']['neutral']);
        $this->assertSame($total, $allMax['meta']['decisive']);
    }

    public function test_the_highlights_render_on_the_page_before_the_generic_sections(): void
    {
        $result = app(HornyTestService::class)->score($this->answers('high', 'low'));

        $html = $this->asAgeVerified()
            ->withSession(['horny_result' => $result])
            ->get('/tw/dual-control/open')
            ->assertOk()
            ->assertSee(__('horny.result.highlights_title'))
            ->getContent();

        // 個人化的重點要排在「這一格的人通常怎樣」之前
        $this->assertLessThan(
            strpos($html, __('horny.result.signals', ['name' => trans('horny.quadrants.open.name')])),
            strpos($html, __('horny.result.highlights_title')),
            '重點應該排在象限的通則內容之前',
        );
    }

    public function test_a_visitor_without_a_score_gets_no_highlights(): void
    {
        // 沒有分數就沒有「你這一份的重點」—— 不能憑空算
        $this->visit('/tw/dual-control/open')
            ->assertOk()
            ->assertDontSee(__('horny.result.highlights_title'));
    }

    public function test_image_slots_stay_invisible_until_a_file_exists(): void
    {
        /* 圖片版位是先留好的。檔案還沒進來的時候整段不能渲染 —— 線上就是線上,
           空框或灰底比沒有那一塊更糟。 */
        $this->assertNull(optional_image('images/dual-control/does-not-exist'));

        $this->visit('/tw/dual-control/open')->assertOk()->assertDontSee('tt-hero-img', false);
        $this->visit('/tw/dual-control')->assertOk()->assertDontSee('tt-hero-img', false);
    }

    public function test_the_old_url_permanently_redirects(): void
    {
        /* 這份測驗當天先上在 /horny-test,同一天改包裝就換了網址。舊網址只活了幾
           小時,但轉址是零成本的,而且它同時是「不要再串接兩層 301」的守門 ——
           性壓抑那組轉址必須直接指到最終網址。 */
        foreach (['/tw/horny-test', '/tw/horny-test/open', '/tw/horny-test/open/og.png'] as $old) {
            $this->asAgeVerified()->get($old)
                ->assertStatus(301)
                ->assertRedirect('/tw/dual-control');
        }

        $this->asAgeVerified()->get('/tw/repression-test')
            ->assertStatus(301)
            ->assertRedirect('/tw/dual-control');
    }

    public function test_the_pages_no_longer_use_the_blunt_name(): void
    {
        /* 對外的包裝改成學術一點的講法(雙控模型),所以「你有多色」「色度」這種
           講法不該再出現在頁面上 —— 改名字最容易漏的就是散在文案裡的那幾處。 */
        foreach (['/tw/dual-control', '/tw/dual-control/simmering'] as $path) {
            $this->visit($path)->assertOk()
                ->assertDontSee('你有多色')
                ->assertDontSee('色度')
                ->assertSee(__('horny.title'));
        }
    }

    public function test_the_measurement_basis_is_computed_from_the_config(): void
    {
        /* 依據如果是手寫的,加題或改反向題就對不上,而對不上的依據比沒有依據更糟。
           所以這裡拿 config 自己數一遍。 */
        $basis = app(HornyTestService::class)->basis();

        $this->assertSame(count(config('horny.questions')), $basis['total']);
        $this->assertTrue($basis['symmetric']);
        $this->assertSame(
            array_sum($basis['axis_counts']),
            $basis['total'],
            '兩條軸的題數加起來要等於總題數 —— 有面向沒掛到軸上',
        );
    }
}
