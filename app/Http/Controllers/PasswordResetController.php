<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
            'email.required' => '請輸入電子信箱',
            'email.email'    => '電子信箱格式不正確',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', '重設連結已寄出，請查收信箱。');
        }

        return back()->withErrors(['email' => __($status)]);
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
            'token.required'            => '重設 token 無效',
            'email.required'            => '請輸入電子信箱',
            'email.email'               => '電子信箱格式不正確',
            'password.required'         => '請輸入新密碼',
            'password.min'              => '密碼至少需要 8 個字元',
            'password.confirmed'        => '兩次輸入的密碼不一致',
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
            return redirect()->route('login')->with('success', '密碼已重設成功，請重新登入。');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
