<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Counter;
use App\Models\TraitResult;
use App\Models\User;
use App\Services\TraitTestService;
use App\Support\QuizSteps;
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

    public function test_the_quiz_page_lists_the_first_page_of_questions(): void
    {
        /* 題目分頁作答(見 QuizSteps)。每一頁都列滿自己那一段,全部題目分在哪幾頁
           由 QuizStepsTest 守 —— 這裡只守第一頁,搜尋引擎進來看到的就是它。 */
        $response = $this->get('/tw/trait-test')->assertOk();
        $texts = __('traits.questions');
        $steps = new QuizSteps('trait_answers', config('traits.questions'), TraitTestService::MIN, TraitTestService::MAX);

        foreach ($steps->questionsOn(1) as $i) {
            $response->assertSee($texts[$i]);
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
           2026-08-25 四個語系都翻好了,所以改成兩面都守:翻好的語系要能索引,
           而語系從 translated 拿掉時,noindex 的保險絲要還在。 */
        $this->assertContains('en', (array) config('traits.translated'));

        $this->get('/en/trait-test')->assertOk()->assertDontSee('noindex', false);
        $this->get('/tw/trait-test')->assertOk()->assertDontSee('noindex', false);

        config(['traits.translated' => ['zh_TW']]);
        $this->get('/en/trait-test')->assertOk()->assertSee('noindex', false);
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

            /* tag 是長條圖上貼在名字底下的小字。名字取得再好都有人看不出在講什麼,
               少一個就等於那一條要點進去才知道 —— 而清單的用途就是不用點進去。
               九個字是版面的硬上限,超過會被 text-overflow 截掉。 */
            $this->assertNotEmpty($item['tag'] ?? null, "{$key} 少了長條圖上的小字 tag");
            $this->assertLessThanOrEqual(
                9,
                mb_strlen($item['tag']),
                "{$key} 的 tag 超過九個字,手機上會被截掉"
            );
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

        /* 這些數字**不再印在結果頁上** —— 計分說明搬到常見問題了。但 basis() 還活著:
           分享卡片(OgImageService)拿它畫光譜傾向,所以計算對不對還是要守。 */
        $this->get('/tw/trait-test/'.__('traits.items.exhib.slug'))->assertOk();
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
            // 這幾行現在貼在長條圖底下,不是獨立的依據區塊
            ->assertSee(__('traits.result.basis_answers', [
                'decisive' => count(config('traits.questions')), 'neutral' => 0,
            ]))
            ->assertSee('tt-dist-note', false);
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

    public function test_no_two_traits_share_a_recognition_signal(): void
    {
        /* 「典型表現」的用途是讓人對照:對上三條以上大概就是你。這個前提是每一條
           只屬於一型 —— 同一句話出現在兩型身上,對得上也不代表什麼,那一條就等於
           沒有作用。複製貼上改文案的時候最容易漏掉。 */
        $seen = [];
        foreach (__('traits.items') as $key => $item) {
            foreach ($item['signals'] as $signal) {
                $this->assertArrayNotHasKey(
                    $signal,
                    $seen,
                    "「{$signal}」同時出現在 {$key} 和 ".($seen[$signal] ?? '?').' 身上'
                );
                $seen[$signal] = $key;

                /* 而且不能只是把那一型的一句話總結再貼一次 —— 讀者在上面
                   已經看過了,重複一次不會讓他多對上任何東西。 */
                $this->assertNotSame($item['line'], $signal, "{$key} 的典型表現跟它的一句話總結一樣");
            }
        }

        $this->assertCount(count(config('traits.traits')) * 4, $seen);
    }

    public function test_the_faq_explains_the_scoring_and_says_it_the_same_way(): void
    {
        /* 計分方式現在**只有一個出口**:常見問題(題目頁與 20 個結果頁都印)。
           結果頁原本那個「這個分數是怎麼算出來的」區塊已經拿掉,所以這裡守的是
           「唯一的那份說明還在,而且跟程式對得上」—— 公式、量表兩端、光譜刻度。 */
        $scoring = collect(__('traits.faq'))
            ->first(fn ($f) => str_contains($f['q'], '分數是怎麼算出來的'));

        $this->assertNotNull($scoring, 'FAQ 少了「這個分數是怎麼算出來的?」這一題');

        $formula = 'Σ(你的答案 × 權重) ÷ Σ(2 × |權重|)';
        $this->assertStringContainsString($formula, $scoring['a'], 'FAQ 沒有把公式寫出來');

        // 量表的兩端也要對得上程式,不然公式寫對了、範圍寫錯了一樣是錯的
        $this->assertStringContainsString(TraitTestService::MIN.'(', $scoring['a']);
        $this->assertStringContainsString('+'.TraitTestService::MAX.'(', $scoring['a']);
        $this->assertStringContainsString(TraitTestService::AXIS_SCALE.'', $scoring['a']);

        // 交卷前後都要看得到 —— 想知道分數怎麼算的人不一定已經做完測驗
        $this->get('/tw/trait-test')->assertOk()->assertSee($scoring['q']);
        $this->get('/tw/trait-test/'.__('traits.items.dom.slug'))->assertOk()->assertSee($scoring['q']);
    }

    public function test_the_names_only_use_the_two_agreed_suffixes(): void
    {
        /* 20 條名字並排的時候,後綴一亂整份清單就不像一套分類 —— 之前同時有
           〜型／〜控／〜派／〜屬性 四種。規則收成兩條:通用後綴一律「〜型」,
           只有使用者本來就會打進搜尋框的既有詞原樣保留。這裡把例外寫死,
           新增屬性想再開一種後綴就會紅燈。 */
        $keptAsIs = ['S屬性', 'M屬性', '抖M', '雙性Switch', '戀愛腦', '老司機'];

        $names = [];
        foreach (__('traits.items') as $key => $item) {
            $name = $item['name'];
            $names[] = $name;

            if (in_array($name, $keptAsIs, true)) {
                continue;
            }

            $this->assertStringEndsWith(
                '型',
                $name,
                "{$key} 的名字「{$name}」既不是「〜型」,也不在保留的既有詞名單裡"
            );
        }

        $this->assertSame($names, array_unique($names), '有兩個屬性同名');
    }

    public function test_each_finisher_gets_the_next_number(): void
    {
        /* 「你是第 N 位」。號碼要真的遞增,而且**匿名的人也要算**:trait_results
           只存登入者,拿它 count() 會漏掉大多數受測者,所以計數是獨立的一張表。 */
        $before = Counter::total(Counter::TRAIT_TEST);

        $this->post('/tw/trait-test', ['a' => $this->allAnswers(2)]);
        $first = session('trait_result')['ordinal'] ?? null;

        $this->post('/tw/trait-test', ['a' => $this->allAnswers(2)]);
        $second = session('trait_result')['ordinal'] ?? null;

        $this->assertSame($before + 1, $first, '第一個人拿到的號碼不是接在既有累計後面');
        $this->assertSame($before + 2, $second, '第二個人沒有拿到下一號');
        $this->assertSame($before + 2, Counter::total(Counter::TRAIT_TEST));

        // 這兩次都是匿名的:trait_results 一筆都沒有,但計數器算到了
        $this->assertDatabaseCount('trait_results', 0);
    }

    public function test_the_number_shows_on_the_result_page_but_not_to_a_visitor(): void
    {
        /* 從搜尋進來的人沒做過測驗 —— 對他講「你是第幾位」是假的,所以那一行
           必須跟著分數走,不是跟著頁面走。 */
        $service = app(TraitTestService::class);
        $result = $service->score($this->allAnswers(2));
        $result['ordinal'] = 4567;
        $slug = __('traits.items.'.$result['top'].'.slug');

        $this->withSession(['trait_result' => $result])
            ->get('/tw/trait-test/'.$slug)
            ->assertOk()
            // 千分位一起驗:number_format 掉了的話這裡會抓到
            ->assertSee(__('traits.result.traveller', [
                'n' => '4,567',
                'total' => count(config('traits.questions')),
            ]));

        /* 一定要先清 session:測試裡的 session 會跨請求活著(Store::start() 把既有
           attributes 併回去),不清的話下面這個「沒有分數的訪客」其實還帶著分數。 */
        $this->flushSession();

        $this->get('/tw/trait-test/'.$slug)
            ->assertOk()
            ->assertDontSee('tt-traveller', false);
    }

    public function test_reloading_the_result_page_does_not_bump_the_number(): void
    {
        /* 號碼在交卷那一刻決定。如果是在結果頁算的,每次重整、每次別人分享點進來
           都會加一,那個數字就變成「頁面被看過幾次」而不是「幾個人做完」。 */
        $this->post('/tw/trait-test', ['a' => $this->allAnswers(2)]);
        $after = Counter::total(Counter::TRAIT_TEST);

        $slug = __('traits.items.'.session('trait_result')['top'].'.slug');
        $this->get('/tw/trait-test/'.$slug)->assertOk();
        $this->get('/tw/trait-test/'.$slug)->assertOk();

        $this->assertSame($after, Counter::total(Counter::TRAIT_TEST), '看結果頁把號碼往上推了');
    }

    public function test_every_bar_carries_its_own_one_liner(): void
    {
        /* 20 條長條如果只有名字,使用者得一條一條點進去才知道在講什麼 ——
           而清單存在的意義就是不用點。小字掉了畫面不會壞,只會變回原來那樣,
           所以這裡逐條對:每一個出現在清單上的名字,底下都要有它的 tag。 */
        $service = app(TraitTestService::class);
        $result = $service->score($this->allAnswers(2));

        $html = $this->withSession(['trait_result' => $result])
            ->get('/tw/trait-test/'.__('traits.items.'.$result['top'].'.slug'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            count($result['traits']),
            substr_count($html, 'tt-bar-tag'),
            '有長條沒有小字'
        );

        foreach (__('traits.items') as $key => $item) {
            $this->assertStringContainsString($item['tag'], $html, "{$key} 的小字沒有印出來");
        }
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

    public function test_the_first_page_still_lists_its_questions_without_javascript(): void
    {
        /* 封面是 JS 加上去的增強。爬蟲與關掉 JS 的人一樣要讀得到第一頁的題目 ——
           收合如果是伺服器端做的,這一頁對搜尋引擎就只剩一顆按鈕。 */
        $html = $this->get('/tw/trait-test')->assertOk()->getContent();
        $texts = __('traits.questions');
        $steps = new QuizSteps('trait_answers', config('traits.questions'), TraitTestService::MIN, TraitTestService::MAX);

        foreach ($steps->questionsOn(1) as $i) {
            $this->assertStringContainsString(e($texts[$i]), $html);
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
