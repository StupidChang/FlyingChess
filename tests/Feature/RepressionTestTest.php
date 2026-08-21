<?php

namespace Tests\Feature;

use App\Services\RepressionTestService;
use Tests\TestCase;

/**
 * 性壓抑指數測驗。
 *
 * 這裡守的是三件會靜靜壞掉、而且壞了沒人會發現的事:
 *
 *   1. 反向題。config 的 dir 一旦寫錯,分數還是算得出來、頁面還是會顯示,
 *      只是全部的人都拿到錯的指數 —— 沒有測試就沒有任何跡象。
 *   2. 級距的邊界。20/40/60/80 是「含下界」,差一格會讓整段落到隔壁頁。
 *   3. 鎖住的深入解讀**不能**出現在 HTML 裡。用 CSS 遮起來等於沒鎖。
 *
 * 請求都當成「已確認年齡的訪客」:AgeVerification 在 web 群組裡,比路由中介層更早跑,
 * 沒帶 cookie 的話讀取型請求拿到的是覆蓋層底下的頁面、寫入型請求則直接被擋下來。
 * 見 TestCase::asAgeVerified 與 AgeGateTest。
 */
class RepressionTestTest extends TestCase
{
    private function visit(string $url)
    {
        return $this->asAgeVerified()->get($url);
    }

    /**
     * 造一份「一路答到底」的答案卷。
     *
     * @param  bool  $maxRepression  true = 每題都往壓抑高的方向答
     * @return array<int, int>
     */
    private function answers(bool $maxRepression): array
    {
        $out = [];
        foreach (config('repression.questions') as $i => $q) {
            $high = ($q['dir'] ?? 1) > 0 ? RepressionTestService::MAX : RepressionTestService::MIN;
            $low = ($q['dir'] ?? 1) > 0 ? RepressionTestService::MIN : RepressionTestService::MAX;
            $out[$i] = $maxRepression ? $high : $low;
        }

        return $out;
    }

    public function test_question_text_and_structure_stay_aligned(): void
    {
        // 第 N 句題目配第 N 個結構。錯位不會報錯,只會讓每個人被算錯面向。
        $this->assertCount(
            count(config('repression.questions')),
            (array) trans('repression.questions', [], 'zh_TW'),
        );
    }

    public function test_every_dimension_has_equal_normal_and_reverse_items(): void
    {
        /* 只有正向題的面向,測到的是「這個人會不會一路按到底」,不是他的狀態。
           而且數量要**對稱**:5:3 的話,每題都按「完全符合」的人基線是 58 不是 50,
           整份測驗默默偏高。加題時很容易破壞這個對稱,所以這裡守住。 */
        $dirs = [];
        foreach (config('repression.questions') as $q) {
            $dirs[$q['dim']][$q['dir']] = ($dirs[$q['dim']][$q['dir']] ?? 0) + 1;
        }

        foreach (array_keys(config('repression.dimensions')) as $dim) {
            $this->assertSame(
                $dirs[$dim][1] ?? 0,
                $dirs[$dim][-1] ?? 0,
                "面向 {$dim} 的正向題與反向題數量不對稱",
            );
        }
    }

    public function test_scoring_hits_both_ends_of_the_index(): void
    {
        $service = app(RepressionTestService::class);

        $high = $service->score($this->answers(true));
        $this->assertSame(100, $high['index']);
        $this->assertSame('very_high', $high['band']);

        $low = $service->score($this->answers(false));
        $this->assertSame(0, $low['index']);
        $this->assertSame('very_low', $low['band']);
    }

    public function test_answering_everything_the_same_lands_mid_scale(): void
    {
        /* 每題都選「完全符合」的人,反向題會把分數抵銷掉 —— 拿到的是中間,
           不是 100。這正是混入反向題的目的,壞掉的話這條會先叫。 */
        $answers = array_fill(0, count(config('repression.questions')), RepressionTestService::MAX);

        $this->assertSame(50, app(RepressionTestService::class)->score($answers)['index']);
    }

    public function test_band_boundaries_are_inclusive_of_their_lower_bound(): void
    {
        $service = app(RepressionTestService::class);

        $this->assertSame('very_low', $service->bandFor(19));
        $this->assertSame('low', $service->bandFor(20));
        $this->assertSame('moderate', $service->bandFor(40));
        $this->assertSame('high', $service->bandFor(60));
        $this->assertSame('very_high', $service->bandFor(80));
    }

    public function test_every_band_page_renders(): void
    {
        foreach (app(RepressionTestService::class)->bands() as $band) {
            $this->visit('/tw/repression-test/'.$band['slug'])
                ->assertOk()
                ->assertSee($band['name'])
                ->assertDontSee('class="age-gate"', false);
        }
    }

    public function test_unknown_band_slug_is_404(): void
    {
        $this->visit('/tw/repression-test/not-a-band')->assertNotFound();
    }

    public function test_submitting_redirects_to_the_matching_band_page(): void
    {
        $this->asAgeVerified()
            ->post('/tw/repression-test', ['a' => $this->answers(true)])
            ->assertRedirect('/tw/repression-test/very-high');
    }

    public function test_incomplete_submission_is_rejected(): void
    {
        $answers = $this->answers(true);
        unset($answers[0]);

        $this->asAgeVerified()
            ->post('/tw/repression-test', ['a' => $answers])
            ->assertSessionHasErrors('a');
    }

    public function test_locked_deep_reading_is_not_rendered_into_the_html(): void
    {
        /* 鎖住的內容塞進 HTML 再用 CSS 遮起來,等於檢視原始碼就破解了。
           訪客(未解鎖)拿到的頁面裡不該出現那段建議的任何一個字。 */
        $band = trans('repression.bands.very_high', [], 'zh_TW');

        $response = $this->visit('/tw/repression-test/very-high')->assertOk();

        $response->assertDontSee($band['advice']);
        $response->assertDontSee($band['partner']);
        foreach ($band['steps'] as $step) {
            $response->assertDontSee($step);
        }
    }

    public function test_watching_an_ad_unlocks_the_deep_reading(): void
    {
        /* 反過來也要測:鎖住時不洩漏之外,解鎖後必須真的顯示。少了這條,
           付費區的欄位名打錯字會靜靜地什麼都不渲染,而畫面上看不出差別。 */
        $band = trans('repression.bands.high', [], 'zh_TW');

        $token = $this->asAgeVerified()->postJson('/tw/ad-unlock/start')->json('token');
        $this->travel(config('premium.rewarded.min_watch_seconds', 15) + 1)->seconds();
        $this->asAgeVerified()->postJson('/tw/ad-unlock/claim', ['token' => $token])
            ->assertJsonPath('ok', true);

        $response = $this->visit('/tw/repression-test/high')->assertOk();

        $response->assertSee($band['advice'])->assertSee($band['partner']);
        foreach ($band['steps'] as $step) {
            $response->assertSee($step);
        }
        $response->assertDontSee(__('repression.result.deep_locked'));
    }

    public function test_a_visitor_without_a_score_still_gets_real_content(): void
    {
        /* 從搜尋或分享連結進來的人沒有分數。在補上這幾段之前,他讀到的只有一句
           總結加一段介紹 —— 五個級距頁對搜尋引擎幾乎是同一頁。 */
        $band = trans('repression.bands.high', [], 'zh_TW');

        $response = $this->visit('/tw/repression-test/high')->assertOk();

        foreach ($band['signals'] as $signal) {
            $response->assertSee($signal);
        }
        $response->assertSee($band['bedroom']);
        foreach ($band['stuck'] as $item) {
            $response->assertSee($item);
        }
        $response->assertSee($band['misread']);

        // 免費變厚不等於把付費那半送出去
        $response->assertDontSee($band['advice']);
    }

    public function test_every_band_has_the_free_and_paid_content_filled_in(): void
    {
        // 少一格就是少一頁內容,而那一頁照樣會被收錄
        foreach (trans('repression.bands', [], 'zh_TW') as $key => $band) {
            $this->assertCount(4, $band['signals'] ?? [], "{$key} 的典型表現不是四條");
            $this->assertCount(3, $band['steps'] ?? [], "{$key} 的具體做法不是三條");
            $this->assertCount(4, $band['stuck'] ?? [], "{$key} 的卡點不是四條");
            foreach (['bedroom', 'misread', 'advice', 'partner'] as $field) {
                $this->assertNotEmpty($band[$field] ?? null, "{$key} 少了 {$field}");
            }
        }
    }

    public function test_the_measurement_basis_is_computed_from_the_config(): void
    {
        /* 依據如果是手寫的,加題或改反向題就對不上,而對不上的依據比沒有依據更糟。
           所以這裡拿 config 自己數一遍。 */
        $basis = app(RepressionTestService::class)->basis();

        $this->assertSame(count(config('repression.questions')), $basis['total']);
        $this->assertTrue($basis['symmetric'], '正反向不對稱的話,依據那一段會宣告一件不成立的事');
        $this->assertCount(count(config('repression.dimensions')), $basis['dimensions']);
        foreach ($basis['dimensions'] as $d) {
            $this->assertSame($d['forward'], $d['reverse'], "{$d['key']} 的正反向題數不相等");
        }

        $this->visit('/tw/repression-test/high')
            ->assertOk()
            ->assertSee(__('repression.result.basis_total_v', ['n' => $basis['total']]))
            ->assertSee(__('repression.result.basis_formula'));
    }

    public function test_band_bounds_come_from_the_config_not_the_label_text(): void
    {
        /* lang 檔的 label(「60–80」)只是給人看的字串。上界必須由下一個級距的
           min 算出來,不然改了 config 而忘了改文案,頁面會顯示一個假的區間。 */
        $service = app(RepressionTestService::class);

        $this->assertSame(['min' => 60, 'max' => 80], $service->range('high'));
        $this->assertSame(100, $service->range('very_high')['max'], '最高的一格上界是 100');
        $this->assertSame(0, $service->range('very_low')['min']);
    }

    public function test_a_score_near_a_band_boundary_says_so(): void
    {
        /* 61 分和 59 分會被分到不同的頁、拿到不同的解讀,但那兩分之差在一份 40 題
           的自陳量表裡沒有意義。不講的話,這一頁看起來比它實際上更確定。 */
        $service = app(RepressionTestService::class);

        $near = $service->confidence($this->fakeResult(61, 'high'));
        $this->assertTrue($near['near_edge']);
        $this->assertSame(1, $near['edge']['dist']);

        $middle = $service->confidence($this->fakeResult(70, 'high'));
        $this->assertFalse($middle['near_edge']);

        $this->asAgeVerified()
            ->withSession(['repression_result' => $this->fakeResult(61, 'high')])
            ->get('/tw/repression-test/high')
            ->assertOk()
            ->assertSee(__('repression.result.basis_edge', [
                'name' => trans('repression.bands.moderate.name', [], 'zh_TW'),
                'dist' => 1,
                'total' => count(config('repression.questions')),
            ]));
    }

    public function test_the_structured_data_only_claims_what_the_page_shows(): void
    {
        // articleBody 寫了頁面上沒有的東西就是 cloaking
        $band = trans('repression.bands.moderate', [], 'zh_TW');
        $html = $this->visit('/tw/repression-test/moderate')->assertOk()->getContent();

        $this->assertStringContainsString($this->forJson($band['bedroom']), $html);
        $this->assertStringNotContainsString($this->forJson($band['advice']), $html);
    }

    /** JSON-LD 裡的樣子:json_encode 會轉義引號,直接比字串會找不到。 */
    private function forJson(string $text): string
    {
        return trim(json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG), '"');
    }

    /**
     * 造一份指定指數的結果,用來測邊界提示 —— 真的去湊出 61 分的答案卷很脆弱,
     * 改一題就壞。
     *
     * @return array<string, mixed>
     */
    private function fakeResult(int $index, string $band): array
    {
        $dimensions = [];
        foreach (array_keys((array) config('repression.dimensions')) as $i => $key) {
            $dimensions[] = ['key' => $key, 'pct' => max(0, $index - $i)];
        }

        return [
            'index' => $index,
            'band' => $band,
            'dimensions' => $dimensions,
            'meta' => ['total' => count(config('repression.questions')), 'decisive' => 10, 'neutral' => 5],
        ];
    }
}
