<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * 註冊、登入、登出、密碼重設、封鎖、Email 驗證閘、Google 登入。
 *
 * 這一整條路徑原本零測試覆蓋 —— 而它是站上唯一「壞了不會有人回報」的地方:
 * 註冊不成功的人不會寄信來說「你們的註冊壞了」,他就走了。SES 出 sandbox 之後
 * 註冊變成真的入口,這裡尤其不能靠手動點。
 *
 * 每一個寫入型請求都要 asAgeVerified():AgeVerification 在 web 群組裡、跑得比
 * 路由中介層早,沒帶 cookie 的 POST 會被它擋下來(見 Tests\TestCase)。
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 限速用的是 cache,測試之間不清會互相污染(尤其登入那把 5 次的鎖)。
        foreach (['login:127.0.0.1', 'register:127.0.0.1', 'register-hourly:127.0.0.1',
            'password-reset-ip:127.0.0.1'] as $key) {
            RateLimiter::clear($key);
        }
    }

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => '小明',
            'email' => 'ming@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ], $overrides);
    }

    // ── 註冊 ──────────────────────────────────────────────

    public function test_registering_creates_the_account_and_sends_the_verification_mail(): void
    {
        Notification::fake();

        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration())
            ->assertRedirect(route('verification.notice', ['locale' => 'tw']));

        $user = User::where('email', 'ming@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at, '註冊當下不該是已驗證');
        $this->assertSame('zh_TW', $user->locale, '註冊語系要跟著網址前綴');
        $this->assertTrue(Hash::check('secret-password', $user->password), '密碼要 hash 過');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_a_nickname_with_angle_brackets_is_rejected(): void
    {
        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration(['name' => '<script>x</script>']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, User::count());
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'ming@example.com']);

        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_a_short_password_or_a_mismatched_confirmation_is_rejected(): void
    {
        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration(['password_confirmation' => 'something-else']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        Notification::fake();

        /* 每次註冊都會寄一封信,而退信率與客訴率算在我們頭上 —— 這把鎖擋的是
           「拿站上當寄信機」,不只是擋重複註冊。 */
        for ($i = 0; $i < 3; $i++) {
            $this->asAgeVerified()->post('/tw/register', $this->validRegistration([
                'email' => "burst{$i}@example.com",
            ]));
        }

        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration(['email' => 'blocked@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertNull(User::where('email', 'blocked@example.com')->first());
    }

    public function test_registration_has_an_hourly_ceiling_on_top_of_the_burst_limit(): void
    {
        Notification::fake();

        /* 只有 60 秒那層的話,慢慢刷一小時可以逼站上寄出上百封信。清掉短鎖
           模擬「等 60 秒再來一次」,長鎖應該還是要把他攔下來。 */
        for ($i = 0; $i < 8; $i++) {
            RateLimiter::clear('register:127.0.0.1');
            $this->asAgeVerified()->post('/tw/register', $this->validRegistration([
                'email' => "slow{$i}@example.com",
            ]));
        }

        RateLimiter::clear('register:127.0.0.1');

        $this->asAgeVerified()
            ->post('/tw/register', $this->validRegistration(['email' => 'ninth@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertNull(User::where('email', 'ninth@example.com')->first());
    }

    // ── 登入 / 登出 ───────────────────────────────────────

    public function test_the_right_password_logs_the_user_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->asAgeVerified()
            ->post('/tw/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('home', ['locale' => 'tw']));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_logs_nobody_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->asAgeVerified()
            ->post('/tw/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_locked_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->asAgeVerified()->post('/tw/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // 第六次連「密碼正確」都不該放行 —— 鎖的是這個 IP,不是這組帳密。
        $this->asAgeVerified()
            ->post('/tw/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_banned_account_cannot_log_in_even_with_the_right_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
            'is_banned' => true,
            'banned_at' => now(),
        ]);

        $this->asAgeVerified()
            ->post('/tw/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        /* Auth::attempt() 會先成功、才被 isBanned() 攔下來,所以這裡真正要確認的
           是「有沒有把他登出、有沒有換掉 session」—— 少了那一段,被封鎖的人
           實際上是登入狀態的。 */
        $this->assertGuest();
    }

    public function test_logging_out_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->asAgeVerified()
            ->post('/tw/logout')
            ->assertRedirect(route('home', ['locale' => 'tw']));

        $this->assertGuest();
    }

    // ── Email 驗證閘 ─────────────────────────────────────

    public function test_an_unverified_account_cannot_reach_the_board_editor(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get('/tw/boards')
            ->assertRedirect(route('verification.notice', ['locale' => 'tw']));
    }

    public function test_a_verified_account_reaches_the_board_editor(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/tw/boards')->assertOk();
    }

    public function test_the_verification_link_marks_the_address_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'locale' => 'tw',
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_a_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // 簽章對得上 id,換成別人的 id 就該失效 —— 否則任何人都能驗證任何帳號。
        $other = User::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'locale' => 'tw',
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
        $tampered = str_replace('/'.$user->getKey().'/', '/'.$other->getKey().'/', $url);

        $this->actingAs($other)->get($tampered)->assertForbidden();

        $this->assertNull($other->fresh()->email_verified_at);
    }

    // ── 密碼重設 ─────────────────────────────────────────

    public function test_the_reset_flow_lets_the_user_log_in_with_the_new_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->asAgeVerified()
            ->post('/tw/forgot-password', ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::createToken($user);

        $this->asAgeVerified()->post('/tw/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login', ['locale' => 'tw']));

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));

        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => 'brand-new-password']));
    }

    public function test_an_unknown_address_gets_the_same_answer_as_a_known_one(): void
    {
        Notification::fake();

        /* 回覆一致是刻意的:訊息不同就等於送對方一個「這個信箱有沒有註冊過」的
           查詢介面,而這是成人站,那份名單本身就是敏感資料。 */
        $this->asAgeVerified()
            ->post('/tw/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_a_bad_reset_token_changes_nothing(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->asAgeVerified()->post('/tw/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    // ── Google 登入 ──────────────────────────────────────

    public function test_google_login_is_404_when_it_is_not_configured(): void
    {
        /* 沒設定就 404 而不是 500:按鈕在沒設定時是隱藏的,所以打到這裡的只會是
           舊書籤或爬蟲。 */
        config(['services.google.client_id' => null]);

        $this->get('/auth/google')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_google_login_redirects_to_google_when_configured(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'https://example.com/auth/google/callback',
        ]);

        $response = $this->get('/auth/google');

        $response->assertRedirectContains('accounts.google.com');
    }
}
