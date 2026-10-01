<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\User;
use App\Services\TraitTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 枕邊屬性測驗的兩人對照(工具頁)。
 *
 * 這一頁最危險的地方不是畫錯圖,是**把付費內容漏出去**:合拍／磨合／給對方的
 * 那一句在結果頁是鎖著的,對照頁一次要顯示兩型的,漏了等於兩倍。
 */
class TraitCompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    public function test_the_page_asks_for_two_types_before_showing_anything(): void
    {
        $this->get('/tw/trait-test/compare')
            ->assertOk()
            ->assertSee(__('traits.compare.empty'))
            ->assertDontSee(__('traits.compare.axis_title'));

        // 只選一邊也還不算一次對照
        $this->get('/tw/trait-test/compare?a=dominant')
            ->assertOk()
            ->assertSee(__('traits.compare.empty'));
    }

    public function test_only_axes_with_a_real_lean_get_drawn(): void
    {
        /* 型別在四條光譜上的訊號很稀疏(20 型裡 15 型只偏一條、3 型完全不偏)。
           四條全畫的話,多數組合會看到三條「兩人都在正中間」的軌道 —— 那不是
           資訊,是版面。沒訊號的軸要收成一行帶過,而不是消失。 */
        $axes = app(TraitTestService::class)->axes();

        $response = $this->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertSee(__('traits.compare.axis_title'))
            ->assertSee(__('traits.compare.summary_title'))
            // S 對 M:支配/臣服那條線一定要畫出來
            ->assertSee($axes['DS']['left'])
            ->assertSee($axes['DS']['right']);

        $comparison = app(TraitTestService::class)->comparison('dom', 'sub');

        $this->assertCount(1, $comparison['rows'], 'S 對 M 只有一條線有訊號');
        $response->assertSee(__('traits.compare.axis_blank', [
            'axes' => implode('、', $comparison['blank']),
        ]));
    }

    public function test_a_pair_with_no_signal_at_all_says_so_instead_of_faking_it(): void
    {
        /* 190 組裡有兩組完全沒有訊號(服務型／雙性Switch 對騷話型):四條光譜都
           不偏、題庫裡也沒有共現。這種組合寧可明說,不要畫四條空軌道假裝有分析。 */
        $this->get('/tw/trait-test/compare?a=pleaser&b=verbal')
            ->assertOk()
            ->assertSee(__('traits.compare.axis_none'))
            ->assertSee(__('traits.compare.signal_none'))
            ->assertDontSee(__('traits.compare.summary_title'));
    }

    public function test_the_pair_verdict_shows_without_leaking_the_paid_wording(): void
    {
        /* 合拍／磨合名單是手寫的,裡面直接點名其他型別。免費揭露的是**有沒有被
           點到**(結論),名單的文字仍然鎖著 —— 這也正好是解鎖的理由。 */
        $service = app(TraitTestService::class);
        $named = $service->pairNamed('dom', 'sub');
        $this->assertTrue($named['match'], 'S 對 M 應該被寫在合拍名單裡');

        $this->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertSee(__('traits.compare.stand_title'))
            ->assertSee(__('traits.compare.named_match'))
            ->assertDontSee($service->item('dom')['match']);
    }

    public function test_the_co_occurrence_numbers_come_from_the_weight_table(): void
    {
        $signal = app(TraitTestService::class)->pairSignal('dom', 'sub');

        // S 對 M 在題庫裡是有反向題的 —— 沒有的話這個訊號等於沒接上
        $this->assertGreaterThan(0, $signal['opposite']);

        $this->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertSee(__('traits.compare.signal_opposite', ['n' => $signal['opposite']]))
            ->assertSee(__('traits.compare.signal_same', ['n' => $signal['same']]));
    }

    public function test_the_pairing_advice_stays_behind_the_paywall(): void
    {
        /* 結果頁把 match／friction／partner_line 放在 @if($unlocked) 裡面。對照頁
           必須是同一條線 —— 這一條就是在守「對照頁不是繞過付費牆的入口」。 */
        $service = app(TraitTestService::class);
        $a = $service->item('dom');
        $b = $service->item('sub');

        $this->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertDontSee($a['match'])
            ->assertDontSee($a['friction'])
            ->assertDontSee($a['partner_line'])
            ->assertDontSee($b['match'])
            ->assertDontSee($b['friction'])
            ->assertDontSee($b['partner_line'])
            ->assertSee(__('traits.compare.deep_locked'));
    }

    public function test_a_premium_user_sees_both_sides_of_the_pairing(): void
    {
        $user = User::factory()->create(['premium_expires_at' => now()->addMonth()]);
        $service = app(TraitTestService::class);

        $this->actingAs($user)
            ->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertSee($service->item('dom')['match'])
            ->assertSee($service->item('sub')['friction'])
            ->assertSee($service->item('dom')['partner_line'])
            ->assertDontSee(__('traits.compare.deep_locked'));
    }

    public function test_the_page_is_noindex_and_points_canonical_at_the_quiz(): void
    {
        /* 20 型兩兩 = 190 種組合。開放索引就是自己跟自己搶字,而 canonical 指向
           某一組組合會讓那 190 個網址互相覆蓋 —— 所以指回題目頁。 */
        $this->get('/tw/trait-test/compare?a=dominant&b=submissive')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,follow"', false)
            ->assertSee('rel="canonical" href="'.route('trait-test.show').'"', false);
    }

    public function test_an_unknown_slug_is_ignored_rather_than_exploding(): void
    {
        /* 網址是人手改、也是被貼來貼去的 —— 打錯字要退回「請選擇」,不是 500。 */
        $this->get('/tw/trait-test/compare?a=no-such-type&b=submissive')
            ->assertOk()
            ->assertSee(__('traits.compare.empty'));
    }

    public function test_comparing_a_type_with_itself_says_so(): void
    {
        $this->get('/tw/trait-test/compare?a=dominant&b=dominant')
            ->assertOk()
            ->assertSee(__('traits.compare.same'))
            ->assertSee(__('traits.compare.axis_title'));
    }

    public function test_the_result_page_links_into_the_comparison_with_its_own_type(): void
    {
        $this->get('/tw/trait-test/dominant')
            ->assertOk()
            ->assertSee(route('trait-test.compare', ['a' => 'dominant']), false);
    }

    public function test_axis_positions_come_from_the_same_rule_as_the_basis_section(): void
    {
        /* 光譜位置與結果頁那段「計分依據」必須同源。兩邊各算一次的話,同一型
           會出現「依據說偏 S、對照圖卻畫在中間」這種自相矛盾,而且不會報錯。 */
        $service = app(TraitTestService::class);

        foreach (array_keys((array) config('traits.traits')) as $key) {
            $profile = $service->axisProfile($key);
            $leans = collect($service->basis($key)['axes'])->pluck('lean')->filter()->all();

            $fromProfile = collect($profile)->pluck('label')->filter()->values()->all();

            sort($leans);
            sort($fromProfile);
            $this->assertSame($leans, $fromProfile, "屬性 {$key} 的依據與對照圖對不上");
        }
    }

    public function test_two_opposite_types_are_marked_as_apart(): void
    {
        $comparison = app(TraitTestService::class)->comparison('dom', 'sub');

        $ds = collect($comparison['rows'])->firstWhere('id', 'DS');

        // S 屬性對 M 屬性,支配/臣服那條線上必須是「各據一邊」,否則這一頁毫無意義
        $this->assertSame('apart', $ds['state']);
        $this->assertNotNull($comparison['tension']);
        $this->assertSame('DS', $comparison['tension']['id']);
    }

    public function test_two_neutral_axes_do_not_count_as_agreement(): void
    {
        /* 「你們在這條線上很像」而依據是雙方都沒有偏好 —— 講了等於沒講,
           所以兩邊都持平的軸不列進 aligned。 */
        $service = app(TraitTestService::class);
        $comparison = $service->comparison('dom', 'dom');

        foreach ($comparison['aligned'] as $id) {
            $profile = $service->axisProfile('dom');
            $this->assertNotNull($profile[$id]['side'], "軸 {$id} 兩邊都持平,不該算成一致");
        }
    }
}
