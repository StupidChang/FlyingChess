<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    /**
     * 已登入但被封鎖的使用者：強制登出並導回登入頁。
     * 必須掛在 set.locale 之後，route('login') 才拿得到 {locale} 預設值。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '此帳號已被停用',
                ], 403);
            }

            return redirect()->route('login')->withErrors(['email' => '此帳號已被停用']);
        }

        return $next($request);
    }
}
