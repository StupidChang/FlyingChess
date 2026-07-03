<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $key = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => __('auth.register_throttle', ['seconds' => $seconds]),
            ])->onlyInput('name', 'email');
        }

        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

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
