<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SiteMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * 站內通知:註冊的歡迎通知、右上角鈴鐺、通知頁,以及後台的單發／群發與改暱稱。
 */
class SiteNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['register:127.0.0.1', 'register-hourly:127.0.0.1'] as $key) {
            RateLimiter::clear($key);
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function message(array $overrides = []): array
    {
        return array_merge(['title' => '週末活動', 'body' => "新棋盤上線了\n快來玩", 'url' => '/tw/game-hall'], $overrides);
    }

    // ── 歡迎通知 ──

    public function test_registering_leaves_a_welcome_notification_in_the_users_language(): void
    {
        $this->asAgeVerified()->post('/jp/register', [
            'name' => 'Ken', 'email' => 'ken@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ]);

        $user = User::where('email', 'ken@example.com')->firstOrFail();
        $n = $user->notifications()->sole();

        $this->assertSame(SiteMessage::KIND_WELCOME, $n->data['kind']);
        $this->assertSame(trans('notifications.welcome_title', [], 'ja'), $n->data['title']);
        $this->assertSame('/jp/game-hall', $n->data['url']);
        $this->assertNull($n->read_at);
    }

    // ── 鈴鐺與通知頁 ──

    public function test_the_bell_shows_the_unread_count_and_the_latest_notifications(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('第一則', '內容一'));
        $user->notify(new SiteMessage('第二則', '內容二'));

        $this->actingAs($user)->asAgeVerified()->get('/tw')
            ->assertOk()
            ->assertSee('第一則')
            ->assertSee('第二則')
            ->assertSee('<span class="nav-notif-dot" >2</span>', false);
    }

    public function test_the_bell_links_to_the_full_notice_not_straight_to_its_link(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('標題', str_repeat('很長的內容', 30), '/tw/game-hall'));
        $id = $user->notifications()->sole()->id;

        // 面板裡點一則是到通知頁的那一則,完整內文也在面板裡(不截斷)
        $this->actingAs($user)->asAgeVerified()->get('/tw')
            ->assertSee('/tw/notifications#n-'.$id, false)
            ->assertSee(str_repeat('很長的內容', 30));

        // 通知頁有完整內容與「前往」按鈕,按鈕才會帶去通知附的連結
        $this->actingAs($user)->asAgeVerified()->get('/tw/notifications')
            ->assertSee('id="n-'.$id.'"', false)
            ->assertSee('/tw/notifications/'.$id, false)
            ->assertSee(__('notifications.open'));
    }

    public function test_a_notice_without_a_link_has_no_go_button(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('t', 'b'));
        $id = $user->notifications()->sole()->id;

        $this->actingAs($user)->asAgeVerified()->get('/tw/notifications')
            ->assertDontSee('/tw/notifications/'.$id, false);
    }

    public function test_system_notices_follow_the_language_being_viewed(): void
    {
        // 用英文註冊的人,切到繁中、日文頁面時,同一則推廣通知要跟著換語言
        $user = User::factory()->create(['locale' => 'en']);
        $user->notify(SiteMessage::promo('en'));
        $id = $user->notifications()->sole()->id;

        foreach (['tw' => 'zh_TW', 'cn' => 'zh_CN', 'jp' => 'ja', 'en' => 'en'] as $prefix => $locale) {
            $title = trans('notifications.promo_title', [], $locale);
            $body = trans('notifications.promo_body', [], $locale);

            $this->actingAs($user)->asAgeVerified()->get("/{$prefix}")
                ->assertSee($title)->assertSee($body);
            $this->actingAs($user)->asAgeVerified()->get("/{$prefix}/notifications")
                ->assertSee($title)->assertSee($body);

            // 「前往」也帶去同一個語系的網址
            $this->actingAs($user)->asAgeVerified()->get("/{$prefix}/notifications/{$id}")
                ->assertRedirect("/{$prefix}/game-hall");
        }
    }

    public function test_an_admin_message_is_shown_as_written_in_every_language(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('只有中文的公告', '內容'));

        $this->actingAs($user)->asAgeVerified()->get('/en/notifications')->assertSee('只有中文的公告');
    }

    public function test_opening_the_notifications_page_marks_everything_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('標題', "第一行\n第二行"));

        $this->actingAs($user)->asAgeVerified()->get('/tw/notifications')
            ->assertOk()
            ->assertSee('nt-item is-new', false)          // 這一次還看得出哪則是新的
            ->assertSee('第一行<br />', false);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_clicking_a_notification_marks_it_read_and_follows_its_link(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('t', 'b', '/tw/game-hall'));
        $n = $user->notifications()->sole();

        $this->actingAs($user)->asAgeVerified()->get("/tw/notifications/{$n->id}")
            ->assertRedirect('/tw/game-hall');
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_somebody_elses_notification_cannot_be_opened(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new SiteMessage('t', 'b'));
        $id = $owner->notifications()->sole()->id;

        $this->actingAs(User::factory()->create())->asAgeVerified()
            ->get("/tw/notifications/{$id}")->assertNotFound();
    }

    public function test_a_stored_link_can_never_lead_off_site(): void
    {
        foreach (['//evil.com', 'https://evil.com', 'javascript:alert(1)', ''] as $bad) {
            $this->assertNull(SiteMessage::safeUrl($bad), $bad);
        }
        $this->assertSame('/tw/x', SiteMessage::safeUrl('/tw/x'));
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->asAgeVerified()->get('/tw/notifications')->assertRedirect();
    }

    // ── 後台 ──

    public function test_an_admin_can_rename_a_user(): void
    {
        $user = User::factory()->create(['name' => '舊名字']);

        $this->actingAs($this->admin())->asAgeVerified()
            ->patch("/tw/admin/users/{$user->id}", ['name' => '新名字', 'is_admin' => 0])
            ->assertSessionHasNoErrors();

        $this->assertSame('新名字', $user->fresh()->name);
    }

    public function test_a_name_with_angle_brackets_is_rejected(): void
    {
        $user = User::factory()->create(['name' => '舊名字']);

        $this->actingAs($this->admin())->asAgeVerified()
            ->patch("/tw/admin/users/{$user->id}", ['name' => '<b>x</b>', 'is_admin' => 0])
            ->assertSessionHasErrors('name');
        $this->assertSame('舊名字', $user->fresh()->name);
    }

    public function test_an_admin_can_notify_one_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->asAgeVerified()
            ->post("/tw/admin/users/{$user->id}/notify", $this->message())
            ->assertSessionHasNoErrors();

        $n = $user->notifications()->sole();
        $this->assertSame('週末活動', $n->data['title']);
        $this->assertSame(SiteMessage::KIND_ADMIN, $n->data['kind']);
    }

    public function test_an_off_site_link_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->asAgeVerified()
            ->post("/tw/admin/users/{$user->id}/notify", $this->message(['url' => '//evil.com']))
            ->assertSessionHasErrors('url');
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_broadcasting_skips_banned_accounts_and_respects_the_audience(): void
    {
        $admin = $this->admin();
        $plain = User::factory()->create();
        $premium = User::factory()->create(['premium_expires_at' => now()->addMonth()]);
        $banned = User::factory()->create(['is_banned' => true, 'banned_at' => now()]);

        $this->actingAs($admin)->asAgeVerified()
            ->post('/tw/admin/users/notify', $this->message(['audience' => 'premium']));
        $this->assertSame(1, $premium->notifications()->count());
        $this->assertSame(0, $plain->notifications()->count());

        $this->actingAs($admin)->asAgeVerified()
            ->post('/tw/admin/users/notify', $this->message(['audience' => 'all']));
        $this->assertSame(1, $plain->notifications()->count());
        $this->assertSame(2, $premium->notifications()->count());
        $this->assertSame(0, $banned->notifications()->count());
    }

    public function test_only_admins_can_send(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->asAgeVerified()
            ->post('/tw/admin/users/notify', $this->message(['audience' => 'all']))
            ->assertForbidden();
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_the_admin_edit_page_shows_what_the_user_already_received(): void
    {
        $user = User::factory()->create();
        $user->notify(new SiteMessage('之前那則', 'b'));

        $this->actingAs($this->admin())->asAgeVerified()
            ->get("/tw/admin/users/{$user->id}/edit")
            ->assertOk()
            ->assertSee('之前那則')
            ->assertSee('name="name"', false);
    }
}
