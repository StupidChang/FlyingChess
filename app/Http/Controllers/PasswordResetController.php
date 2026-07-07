<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * 顯示忘記密碼表單
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * 寄送重設連結
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('auth.email_required'),
            'email.email'    => __('auth.email_invalid'),
        ]);

        // Rate limit: 3 attempts per hour per IP+email composite key.
        // Prevents email enumeration via differential responses and reset-link spam.
        $key = 'password-reset:' . $request->ip() . ':' . strtolower($request->input('email'));
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => __('auth.reset_throttle', ['seconds' => $seconds]),
            ])->withInput();
        }
        RateLimiter::hit($key, 3600);

        Password::sendResetLink($request->only('email'));

        // Always return the same message regardless of whether the email exists,
        // to prevent attackers from enumerating registered addresses.
        return back()->with('success', __('auth.reset_link_sent'));
    }

    /**
     * 顯示重設密碼表單
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * 執行密碼重設
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ], [
            'token.required'            => __('auth.reset_token_invalid'),
            'email.required'            => __('auth.email_required'),
            'email.email'               => __('auth.email_invalid'),
            'password.required'         => __('auth.password_required'),
            'password.min'              => __('auth.password_min'),
            'password.confirmed'        => __('auth.password_mismatch'),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __('auth.reset_success'));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
