<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SiteMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => __('auth.throttle', ['seconds' => $seconds]),
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (Auth::user()->isBanned()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => __('auth.account_disabled'),
                ])->onlyInput('email');
            }

            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        RateLimiter::hit($key, 60);

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        /* 兩層限制。短的那層擋連點,長的那層擋「慢慢刷」——
           每次註冊都會寄一封驗證信,只有 60 秒那層的話,一個 IP 一小時可以
           逼站上寄出 180 封信,退信率與客訴率都是算在我們頭上的。 */
        $burst = 'register:'.$request->ip();
        $hourly = 'register-hourly:'.$request->ip();

        foreach ([[$burst, 3], [$hourly, 8]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return back()->withErrors([
                    'email' => __('auth.register_throttle', [
                        'seconds' => RateLimiter::availableIn($key),
                    ]),
                ])->onlyInput('name', 'email');
            }
        }

        RateLimiter::hit($burst, 60);
        RateLimiter::hit($hourly, 3600);

        $data = $request->validate([
            // 角括號在暱稱裡永遠不是正常字元,擋掉當作縱深防禦。真正的 XSS 防線是
            // 輸出端的跳脫(admin 面板已改用 data 屬性 + dataset,不再把暱稱拼進 JS
            // 字串),這條只是讓惡意 payload 連存都存不進來。撇號等合法字元不擋。
            'name' => ['required', 'string', 'max:50', 'regex:/^[^<>]+$/'],
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => app()->getLocale(),
            'password' => bcrypt($data['password']),
        ]);

        // 站內歡迎通知,用註冊當下的語系寫好存起來(見 SiteMessage)
        $user->notify(SiteMessage::welcome($user->locale));

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')->with('success', __('auth.register_success'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
