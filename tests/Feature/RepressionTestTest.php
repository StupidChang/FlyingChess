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

        $this->visit('/tw/repression-test/very-high')
            ->assertOk()
            ->assertDontSee($band['advice']);
    }
}
