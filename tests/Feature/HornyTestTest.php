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
            $this->visit('/tw/horny-test/'.$q['slug'])
                ->assertOk()
                ->assertSee($q['name'])
                ->assertDontSee('class="age-gate"', false);
        }
    }

    public function test_the_question_page_renders(): void
    {
        $this->visit('/tw/horny-test')
            ->assertOk()
            ->assertSee(__('horny.h1'))
            // 兩條軸的說明是這一頁對搜尋引擎唯一說得出「在測什麼」的內容
            ->assertSee(__('horny.axes.desire.name'))
            ->assertSee(__('horny.axes.brake.name'));
    }

    public function test_unknown_quadrant_slug_is_404(): void
    {
        $this->visit('/tw/horny-test/not-a-quadrant')->assertNotFound();
    }

    public function test_submitting_redirects_to_the_matching_quadrant(): void
    {
        $this->asAgeVerified()
            ->post('/tw/horny-test', ['a' => $this->answers('high', 'high')])
            ->assertRedirect('/tw/horny-test/simmering');
    }

    public function test_incomplete_submission_is_rejected(): void
    {
        $answers = $this->answers('high', 'high');
        unset($answers[0]);

        $this->asAgeVerified()
            ->post('/tw/horny-test', ['a' => $answers])
            ->assertSessionHasErrors('a');
    }

    public function test_locked_deep_reading_is_not_rendered_into_the_html(): void
    {
        /* 鎖住的內容塞進 HTML 再用 CSS 遮起來,等於檢視原始碼就破解了。
           訪客(未解鎖)拿到的頁面裡不該出現那段建議的任何一個字。 */
        $quad = trans('horny.quadrants.simmering', [], 'zh_TW');

        $response = $this->visit('/tw/horny-test/simmering')->assertOk();

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

        $response = $this->visit('/tw/horny-test/open')->assertOk();

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

        $response = $this->visit('/tw/horny-test/locked')->assertOk();

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
