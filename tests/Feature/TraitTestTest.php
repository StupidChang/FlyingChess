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

    public function test_question_text_and_structure_stay_aligned(): void
    {
        /* 第 N 句題目配第 N 個結構(權重與光譜方向)。錯位不會報錯,也不會讓
           畫面壞掉 —— 只會讓每個人被算成錯的屬性。改題目的時候最容易踩到。 */
        $this->assertCount(
            count(config('traits.questions')),
            (array) trans('traits.questions', [], 'zh_TW'),
        );
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

    public function test_a_visitor_without_a_score_still_gets_real_content(): void
    {
        /* 從搜尋進來的人沒有分數。在補上這幾段之前,他讀到的只有一句總結加一段
           介紹 —— 20 個結果頁對搜尋引擎幾乎是同一頁。免費區必須自己站得住。 */
        $item = __('traits.items.exhib');

        $response = $this->get('/tw/trait-test/'.$item['slug'])->assertOk();

        foreach ($item['signals'] as $signal) {
            $response->assertSee($signal);
        }
        $response->assertSee($item['bedroom']);
        foreach ($item['likes'] as $like) {
            $response->assertSee($like);
        }
        $response->assertSee($item['everyday']);

        // 而深入解讀還是鎖著的 —— 免費變多不等於把付費那半送出去
        $response->assertDontSee($item['deep']);
        $response->assertDontSee($item['partner_line']);
    }

    public function test_every_trait_has_the_free_content_filled_in(): void
    {
        // 少一型就是少一頁內容,而那一頁照樣會被收錄
        foreach (__('traits.items') as $key => $item) {
            $this->assertCount(4, $item['signals'] ?? [], "{$key} 的典型表現不是四條");
            $this->assertNotEmpty($item['bedroom'] ?? null, "{$key} 少了「在床上長什麼樣子」");
            $this->assertCount(4, $item['likes'] ?? [], "{$key} 的常見偏好不是四條");
            $this->assertNotEmpty($item['everyday'] ?? null, "{$key} 少了「不只在床上」");
        }
    }

    public function test_the_measurement_basis_is_computed_from_the_weight_table(): void
    {
        /* 依據如果是手寫的,題目一改就對不上,而對不上的依據比沒有依據更糟。
           所以這裡拿 config 自己數一遍,跟頁面上的數字對。 */
        $expected = 0;
        $reverse = 0;
        foreach (config('traits.questions') as $q) {
            $w = $q['weights']['exhib'] ?? 0;
            if ($w !== 0) {
                $expected++;
                $w < 0 and $reverse++;
            }
        }

        $basis = app(TraitTestService::class)->basis('exhib');

        $this->assertSame($expected, $basis['count']);
        $this->assertSame($reverse, $basis['reverse']);
        $this->assertSame(count(config('traits.questions')), $basis['total']);

        $this->get('/tw/trait-test/'.__('traits.items.exhib.slug'))
            ->assertOk()
            ->assertSee(__('traits.result.basis_questions_v', ['n' => $expected, 'total' => $basis['total']]))
            ->assertSee(__('traits.result.basis_formula'))
            ->assertSee(__('traits.result.basis_limits', ['total' => $basis['total']]));
    }

    public function test_a_trait_measured_by_too_few_questions_claims_no_axis_lean(): void
    {
        /* 一兩題就宣稱「這個屬性偏某一側」是拿雜訊當依據。少於 AXIS_MIN 題不列 ——
           寧可少講一條,這一段的用途就是可信。 */
        foreach (app(TraitTestService::class)->basis('dom')['axes'] as $axis) {
            $this->assertGreaterThanOrEqual(TraitTestService::AXIS_MIN, $axis['total']);
        }
    }

    public function test_related_traits_come_from_shared_questions(): void
    {
        $service = app(TraitTestService::class);
        $related = $service->related('voyeur');

        // 偷窺與露出共用題目(「知道有人在看」那幾題兩邊都餵),所以必須互相連得到
        $keys = array_column($related['together'], 'key');
        $this->assertContains('exhib', $keys);

        // 反面清單只放真的反向計分的:Switch 的題目給 S/M 各 -1
        $this->assertContains('dom', array_column($service->related('switch')['against'], 'key'));

        $this->get('/tw/trait-test/'.__('traits.items.voyeur.slug'))
            ->assertOk()
            ->assertSee(route('trait-test.result', ['slug' => __('traits.items.exhib.slug')]), false);
    }

    public function test_the_result_says_when_the_top_traits_are_tied(): void
    {
        /* 領先一個百分點也印一頂王冠,是這類測驗最容易誤導人的地方。
           全部答「完全是」的話 20 種都是 100%,那就該講出來。 */
        $service = app(TraitTestService::class);
        $result = $service->score($this->allAnswers(2));
        $confidence = $service->confidence($result);

        $this->assertSame(0, $confidence['gap']);
        $this->assertNotEmpty($confidence['tied']);
        $this->assertSame(count(config('traits.traits')), $confidence['strong']);

        $this->withSession(['trait_result' => $result])
            ->get('/tw/trait-test/'.__('traits.items.'.$result['top'].'.slug'))
            ->assertOk()
            ->assertSee(__('traits.result.basis_your_title'))
            ->assertSee(__('traits.result.basis_answers', [
                'decisive' => count(config('traits.questions')), 'neutral' => 0,
            ]));
    }

    public function test_the_structured_data_only_claims_what_the_page_shows(): void
    {
        /* articleBody 寫了頁面上沒有的東西就是 cloaking。鎖住的深入解讀不能進去。 */
        $item = __('traits.items.tease');
        $html = $this->get('/tw/trait-test/'.$item['slug'])->assertOk()->getContent();

        $json = json_encode($item['signals'][0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString(trim($json, '"'), $html);
        $this->assertStringNotContainsString(
            trim(json_encode($item['deep'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"'),
            $html
        );
    }

    public function test_the_compass_plots_the_axes_and_only_when_there_is_a_score(): void
    {
        /* 象限圖是伺服器端算好座標的 SVG(沒有 JS)。這裡守兩件事:落點必須在畫布
           範圍內 —— 算錯的話點會跑到圖外面,而畫面上只會看起來「沒有點」;
           以及沒有分數的人不該看到一張空圖。 */
        $service = app(TraitTestService::class);
        $result = $service->score($this->allAnswers(2));

        $html = $this->withSession(['trait_result' => $result])
            ->get('/tw/trait-test/'.__('traits.items.'.$result['top'].'.slug'))
            ->assertOk()
            ->getContent();

        preg_match_all('/tt-map-dot" cx="([0-9.]+)" cy="([0-9.]+)"/', $html, $m);
        $this->assertCount(2, $m[1], '四條光譜要畫成兩張圖');

        foreach (array_merge($m[1], $m[2]) as $coord) {
            $this->assertGreaterThanOrEqual(0, (float) $coord);
            $this->assertLessThanOrEqual(100, (float) $coord);
        }

        $this->get('/tw/trait-test/'.__('traits.items.tease.slug'))
            ->assertOk()
            ->assertDontSee('tt-map-dot', false);
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

    public function test_the_test_is_reachable_from_every_page(): void
    {
        /* 沒有入口的頁面等於不存在 —— 使用者找不到,爬蟲也只能靠 sitemap。
           頁尾在每一頁都出現,是站內連結最有效的位置。 */
        foreach (['/tw', '/tw/game-hall', '/tw/truth-dare'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(route('trait-test.show'), false);
        }
    }

    public function test_the_hall_lists_it_in_the_structured_data(): void
    {
        // 大廳的 ItemList 是「這個站有哪些東西」的機器可讀版本
        $this->get('/tw/game-hall')
            ->assertOk()
            ->assertSee('"'.route('trait-test.show').'"', false);
    }

    public function test_the_page_carries_its_own_seo_not_the_site_default(): void
    {
        $html = $this->get('/tw/trait-test')->assertOk()->getContent();

        // 標題、描述、canonical 都要是這一頁自己的,不能吃站台預設
        $this->assertStringContainsString('<title>'.__('traits.seo.title'), $html);
        $this->assertStringContainsString(__('traits.seo.description'), $html);
        $this->assertStringContainsString('rel="canonical" href="'.route('trait-test.show').'"', $html);
        $this->assertStringNotContainsString(__('seo.home_description'), $html);

        // Quiz 結構化資料,給 Google 與 AI 搜尋讀
        $this->assertStringContainsString('"@type":"Quiz"', $html);
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
