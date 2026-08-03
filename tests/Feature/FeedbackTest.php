<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 站內回報。
 *
 * 在這之前唯一的管道是 SUPPORT_EMAIL —— 要開信箱、要打地址,而按下「回報問題」
 * 的那一刻使用者通常正在煩。表單留在站內,順手就送得出來。
 */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => Feedback::TYPE_BUG,
            'message' => '在手機上按「開始遊戲」沒有反應。',
        ], $overrides);
    }

    public function test_the_form_is_reachable_without_an_account(): void
    {
        $this->get('/tw/feedback')
            ->assertOk()
            ->assertSee(__('feedback.h1'))
            ->assertSee(__('feedback.type_bug'))
            ->assertSee(__('feedback.type_prompt'))
            ->assertSee(__('feedback.type_feature'))
            ->assertSee(__('feedback.type_other'));
    }

    public function test_a_guest_can_send_one(): void
    {
        $this->post('/tw/feedback', $this->payload(['contact' => '@my_ig']))
            ->assertRedirect(route('feedback.show'))
            ->assertSessionHas('feedback_ok');

        $this->assertDatabaseCount('feedback', 1);

        $row = Feedback::first();
        $this->assertSame(Feedback::TYPE_BUG, $row->type);
        $this->assertSame('@my_ig', $row->contact);
        $this->assertSame('zh_TW', $row->locale);
        $this->assertNull($row->user_id);
        $this->assertSame(Feedback::STATUS_NEW, $row->status);
    }

    public function test_a_logged_in_report_carries_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tw/feedback', $this->payload())->assertRedirect();

        $this->assertSame($user->id, Feedback::first()->user_id);
    }

    public function test_the_thank_you_panel_replaces_the_form(): void
    {
        $this->post('/tw/feedback', $this->payload());

        $this->followingRedirects()
            ->post('/tw/feedback', $this->payload())
            ->assertOk()
            ->assertSee(__('feedback.thanks_title'))
            // 確認畫面出現時表單要收起來,不然看起來像沒送出去
            ->assertDontSee('name="message"', false);
    }

    public function test_it_rejects_an_empty_or_bogus_report(): void
    {
        $this->post('/tw/feedback', $this->payload(['message' => '']))
            ->assertSessionHasErrors('message');

        $this->post('/tw/feedback', $this->payload(['type' => 'nonsense']))
            ->assertSessionHasErrors('type');

        $this->post('/tw/feedback', $this->payload(['message' => str_repeat('字', 2001)]))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_a_report_about_blocked_words_still_gets_through(): void
    {
        /* 這是整個功能最重要的一條。站上其他所有輸入都套 NoBlockedWords,
           但檢舉「有人在棋盤上寫了 X」如果被擋詞規則攔下來,我們就永遠不會
           知道那件事。回報管道刻意不做內容過濾。 */
        $report = '社群棋盤 #12 有人把格子內容寫成約砲,請處理';

        $this->post('/tw/feedback', $this->payload(['message' => $report]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($report, Feedback::first()->message);
    }

    public function test_the_honeypot_swallows_a_bot_without_telling_it(): void
    {
        $this->post('/tw/feedback', $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect(route('feedback.show'))
            // 一樣回成功畫面 —— 回錯誤只是在教機器人下次避開哪個欄位
            ->assertSessionHas('feedback_ok')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_the_source_page_is_carried_but_only_if_it_is_ours(): void
    {
        // 表單會從 ?from= 預填
        $this->get('/tw/feedback?from=/tw/wheel-game')
            ->assertOk()
            ->assertSee('value="/tw/wheel-game"', false);

        // 站外網址、協定相對網址一律丟掉,不要存也不要回填
        foreach (['//evil.example/x', 'https://evil.example', 'javascript:alert(1)', 'tw/no-slash'] as $bad) {
            $this->assertNull(Feedback::sanitizePagePath($bad), $bad);
        }

        $this->post('/tw/feedback', $this->payload(['page_path' => '//evil.example/x']))->assertRedirect();
        $this->assertNull(Feedback::first()->page_path);
    }

    public function test_the_form_is_linked_from_the_footer_and_the_beta_notice(): void
    {
        config(['beta.notice' => true]);

        $html = $this->get('/tw/wheel-game')->assertOk()->getContent();

        // 頁尾與公告都要連得到,而且帶上現在這一頁
        $this->assertStringContainsString(route('feedback.show', ['from' => '/tw/wheel-game']), $html);
        $this->assertStringContainsString(__('feedback.nav'), $html);
    }

    public function test_it_stays_out_of_the_index(): void
    {
        // 表單頁沒有搜尋價值,但 follow 要留著,不要變成爬蟲的死路
        $this->get('/tw/feedback')->assertOk()->assertSee('content="noindex,follow"', false);
        $this->get('/sitemap-tw.xml')->assertOk()->assertDontSee('/feedback</loc>', false);
    }

    public function test_every_locale_has_the_page(): void
    {
        foreach (['/tw/feedback', '/en/feedback', '/cn/feedback', '/jp/feedback'] as $path) {
            $this->get($path)->assertOk()->assertSee('name="message"', false);
        }
    }
}
