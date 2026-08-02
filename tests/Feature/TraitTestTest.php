<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\TraitResult;
use App\Models\User;
use App\Services\TraitTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 枕邊屬性測驗。
 */
class TraitTestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    /** 全部答同一個值,方便造出可預期的分數。 */
    private function allAnswers(int $v): array
    {
        return array_fill(0, count(config('traits.questions')), $v);
    }

    public function test_the_quiz_page_lists_every_question(): void
    {
        $response = $this->get('/tw/trait-test')->assertOk();

        foreach (__('traits.questions') as $q) {
            $response->assertSee($q);
        }
    }

    public function test_the_weight_table_never_reaches_the_browser(): void
    {
        /* 權重表等於這個測驗的答案卷。送到瀏覽器的話,別人抄走的就不只是
           三十句題目,是整個測驗 —— 所以計分只在伺服器做。 */
        $html = $this->get('/tw/trait-test')->assertOk()->getContent();

        $this->assertStringNotContainsString('weights', $html);
        foreach (array_keys(config('traits.traits')) as $key) {
            $this->assertStringNotContainsString('"'.$key.'"', $html, "屬性代碼 {$key} 不該出現在頁面上");
        }
    }

    public function test_submitting_lands_on_the_matching_result_page(): void
    {
        $service = app(TraitTestService::class);
        $answers = $this->allAnswers(2);
        $expected = $service->score($answers);

        $this->post('/tw/trait-test', ['a' => $answers])
            ->assertRedirect(route('trait-test.result', ['slug' => $service->slug($expected['top'])]));
    }

    public function test_an_incomplete_submission_is_rejected(): void
    {
        $answers = $this->allAnswers(1);
        unset($answers[5]);

        $this->post('/tw/trait-test', ['a' => $answers])->assertSessionHasErrors('a');
    }

    public function test_an_out_of_range_answer_is_rejected(): void
    {
        $answers = $this->allAnswers(1);
        $answers[0] = 99;

        $this->post('/tw/trait-test', ['a' => $answers])->assertSessionHasErrors('a.0');
    }

    public function test_a_logged_in_result_is_kept_for_the_timeline(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tw/trait-test', ['a' => $this->allAnswers(2)]);

        $this->assertSame(1, TraitResult::where('user_id', $user->id)->count());
        $row = TraitResult::first();
        $this->assertNotEmpty($row->traits);
        $this->assertCount(count(config('traits.axes')), $row->axes);
    }

    public function test_an_anonymous_result_is_not_stored(): void
    {
        // 沒帳號就沒有地方顯示時間軸,存了也只是留著別人最私密的作答
        $this->post('/tw/trait-test', ['a' => $this->allAnswers(2)]);

        $this->assertSame(0, TraitResult::count());
    }

    public function test_every_trait_has_its_own_page(): void
    {
        foreach (__('traits.items') as $item) {
            $this->get('/tw/trait-test/'.$item['slug'])
                ->assertOk()
                ->assertSee($item['name'])
                ->assertSee($item['long']);
        }
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get('/tw/trait-test/not-a-real-trait')->assertNotFound();
    }

    public function test_a_result_page_reached_without_taking_the_quiz_still_has_content(): void
    {
        /* 從搜尋或分享連結進來的人沒有分數。那一頁還是要有內容可讀,
           不然對搜尋引擎來說就是一頁空的。 */
        $item = __('traits.items.tease');

        $this->get('/tw/trait-test/'.$item['slug'])
            ->assertOk()
            ->assertSee($item['long'])
            ->assertDontSee(__('traits.result.crown'));
    }

    public function test_somebody_elses_score_is_not_shown_on_the_wrong_page(): void
    {
        $service = app(TraitTestService::class);
        $answers = $this->allAnswers(2);
        $result = $service->score($answers);

        // 帶著 A 的分數去看 B 的頁面 —— 網址與分數對不起來,不能硬套
        $other = collect(__('traits.items'))->keys()->first(fn ($k) => $k !== $result['top']);

        $this->withSession(['trait_result' => $result])
            ->get('/tw/trait-test/'.__('traits.items.'.$other.'.slug'))
            ->assertOk()
            ->assertDontSee(__('traits.result.crown'));
    }

    public function test_the_result_pages_are_in_the_sitemap(): void
    {
        $xml = $this->get('/sitemap-tw.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/trait-test</loc>', $xml);
        foreach (__('traits.items') as $item) {
            $this->assertStringContainsString('/trait-test/'.$item['slug'], $xml);
        }
    }

    public function test_an_untranslated_locale_is_not_indexed(): void
    {
        /* 讓搜尋引擎收錄一頁中文內容配英文網址,對排名是扣分不是加分。
           翻好之後把語系加進 config/traits.php 的 translated。 */
        $this->assertNotContains('en', (array) config('traits.translated'));

        $this->get('/en/trait-test')->assertOk()->assertSee('noindex', false);
        $this->get('/tw/trait-test')->assertOk()->assertDontSee('noindex', false);
    }

    public function test_scoring_puts_a_flat_no_at_zero_not_at_half(): void
    {
        /* 「完全不像」就該是 0%,不是 50% —— 這跟光譜不一樣:光譜是兩極之間的
           位置,屬性是「你有多像它」。 */
        $result = app(TraitTestService::class)->score($this->allAnswers(0));

        foreach ($result['traits'] as $t) {
            $this->assertSame(0, $t['pct']);
        }
    }

    public function test_the_deep_reading_is_not_in_the_html_when_locked(): void
    {
        $item = __('traits.items.tease');

        /* 鎖住的內容如果照樣渲染、只是用 CSS 遮起來,檢視原始碼就破解了 ——
           那跟沒有鎖一樣。所以鎖住時伺服器根本不輸出那段文字。 */
        $this->get('/tw/trait-test/'.$item['slug'])
            ->assertOk()
            ->assertSee(__('traits.result.deep_locked'))
            ->assertDontSee($item['deep']);
    }

    public function test_watching_an_ad_unlocks_the_deep_reading(): void
    {
        $item = __('traits.items.tease');

        $token = $this->postJson('/tw/ad-unlock/start')->json('token');
        $this->travel(config('premium.rewarded.min_watch_seconds', 15) + 1)->seconds();
        $this->withCredentials()->postJson('/tw/ad-unlock/claim', ['token' => $token])
            ->assertJsonPath('ok', true);

        $this->withCredentials()->get('/tw/trait-test/'.$item['slug'])
            ->assertOk()
            ->assertSee($item['deep'])
            ->assertDontSee(__('traits.result.deep_locked'));
    }

    public function test_the_axis_reading_follows_the_actual_score(): void
    {
        $service = app(TraitTestService::class);

        /* 「同一型的每個人拿到同一份範本」是這類測驗最常被批評的地方。
           這一段必須跟著實際分數走,不是照主屬性查表。 */
        $left = $service->axisReading(['DS' => 8, 'PE' => 0, 'OR' => -8, 'IG' => 0]);

        $this->assertSame(__('traits.axis_reading.DS.left'), $left['DS']['text']);
        $this->assertSame(__('traits.axis_reading.OR.right'), $left['OR']['text']);
        $this->assertSame(__('traits.axis_reading.PE.mid'), $left['PE']['text'], '接近中間就該講「兩邊都有」');
        $this->assertNull($left['PE']['lean']);
    }

    public function test_the_quiz_still_lists_every_question_without_javascript(): void
    {
        /* 封面是 JS 加上去的增強。爬蟲與關掉 JS 的人一樣要讀得到全部題目 ——
           收合如果是伺服器端做的,這一頁對搜尋引擎就只剩一顆按鈕。 */
        $html = $this->get('/tw/trait-test')->assertOk()->getContent();

        foreach (__('traits.questions') as $q) {
            $this->assertStringContainsString($q, $html);
        }
    }

    public function test_no_page_prints_a_raw_translation_key(): void
    {
        /* __() 找不到 key 時回傳 key 本身,而那是 truthy —— 所以
           __('x') ?: '後備' 的後備永遠不會生效,畫面上會直接出現「ui.faq」。
           肉眼很容易漏掉,測試掃一次比較快。 */
        foreach (['/tw/trait-test', '/tw/trait-test/tease'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/>\s*(ui|traits|games|minigame)\.[a-z_.]+\s*</',
                $html,
                "{$url} 印出了未翻譯的 key"
            );
        }
    }

    public function test_the_profile_shows_the_timeline(): void
    {
        $user = User::factory()->create();
        $service = app(TraitTestService::class);

        foreach ([2, -2] as $v) {
            $r = $service->score($this->allAnswers($v));
            TraitResult::create([
                'user_id' => $user->id, 'top_trait' => $r['top'],
                'traits' => $r['traits'], 'axes' => $r['axes'],
            ]);
        }

        $this->actingAs($user)->get('/tw/profile')
            ->assertOk()
            ->assertSee(__('traits.profile.heading'))
            ->assertSee('tt-spark', false);
    }

    public function test_the_profile_says_so_when_there_is_nothing_yet(): void
    {
        $this->actingAs(User::factory()->create())->get('/tw/profile')
            ->assertOk()
            ->assertSee(__('traits.profile.empty'));
    }
}
