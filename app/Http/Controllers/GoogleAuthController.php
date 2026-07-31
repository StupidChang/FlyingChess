<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google 登入(Socialite)。
 *
 * 設計取捨:
 *  - 未設定憑證時一律 404,而不是丟出設定錯誤 —— 這樣「還沒接 Google」的
 *    環境行為是明確的,也不會在錯誤頁洩漏設定狀態。
 *  - 以 email 對應既有帳號:同一個 email 用密碼註冊過,之後用 Google 登入
 *    會登入同一個帳號,不會產生重複使用者。
 *  - Google 已驗證過 email,所以直接標記 email_verified_at。
 *  - 被停權的帳號一律拒絕,與密碼登入的行為一致。
 */
class GoogleAuthController extends Controller
{
    /** 三個設定值都必須齊全,否則視為未啟用。 */
    private function enabled(): bool
    {
        return (bool) (config('services.google.client_id')
            && config('services.google.client_secret')
            && config('services.google.redirect'));
    }

    public function redirect(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);

        try {
            $g = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // 使用者中途取消、state 過期、或 Google 回傳錯誤
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.google_failed')]);
        }

        $email = $g->getEmail();
        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.google_no_email')]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = new User;
            $user->email = $email;
            // Google 帳號沒有本站密碼,給一組隨機值佔位(使用者可日後用忘記密碼設定)
            $user->password = bcrypt(Str::random(40));
        }

        if ($user->is_banned) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.banned')]);
        }

        $user->name = $user->name ?: (Str::of((string) $g->getName())->trim()->limit(40, '')->toString()
            ?: Str::before($email, '@'));
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->locale = $user->locale ?: app()->getLocale();
        $user->save();

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
