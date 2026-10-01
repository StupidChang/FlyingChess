<?php

namespace Tests\Feature;

use App\Http\Middleware\AgeVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 沒驗證信箱的帳號只能「玩」,不能「留下東西」。
 *
 * 註冊有限流(AuthController::register:每分鐘 3 次、每小時 8 次),但限流只是讓
 * 大量註冊變慢;真正讓假帳號沒有用處的是這一層 —— 不驗證就建不了棋盤、發不了社群、
 * 公開不了個人頁,也不會出現在「尋找」清單裡。
 */
class EmailVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AgeVerification::class);
    }

    public function test_an_unverified_member_is_sent_to_verify_before_creating_anything(): void
    {
        $user = User::factory()->unverified()->create();
        $notice = route('verification.notice', ['locale' => 'tw']);

        foreach (['/tw/boards', '/tw/boards/create', '/tw/profile', '/tw/profile/edit', '/tw/my-wheels', '/tw/my-dice'] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect($notice);
        }

        $this->actingAs($user)->post('/tw/boards', ['name' => 'x'])->assertRedirect($notice);
        $this->actingAs($user)->post('/tw/premium/checkout')->assertRedirect($notice);
        $this->assertSame(0, $user->boards()->count());
    }

    public function test_an_unverified_account_never_shows_up_publicly(): void
    {
        // 就算資料庫裡被設成公開(例如之後多了別的入口),沒驗證也不公開
        $ghost = User::factory()->unverified()->create(['name' => '假帳號', 'profile_public' => true]);
        $real = User::factory()->create(['name' => '真會員', 'profile_public' => true]);

        $this->get('/tw/u/'.$ghost->id)->assertNotFound();
        $this->get('/tw/u/'.$real->id)->assertOk();

        $this->get('/tw/discover')->assertOk()
            ->assertDontSee('假帳號')
            ->assertSee('真會員');
    }

    public function test_the_verify_page_says_what_verifying_unlocks(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/tw/email/verify')->assertOk()
            ->assertSee(__('auth.verify_email_unlocks_title'))
            ->assertSee(__('auth.verify_email_unlocks')[0]);
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post('/tw/register', [
                'name' => "user{$i}", 'email' => "u{$i}@example.com",
                'password' => 'password123', 'password_confirmation' => 'password123',
            ]);
            auth()->logout();
        }

        $this->post('/tw/register', [
            'name' => 'user4', 'email' => 'u4@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'u4@example.com']);
    }
}
