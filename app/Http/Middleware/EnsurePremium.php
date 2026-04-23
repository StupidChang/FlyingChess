<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPremium()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '此功能需要付費會員，請先升級。',
                ], 403);
            }

            return redirect()->route('premium.index')
                ->with('error', '此功能需要付費會員，請先升級。');
        }

        return $next($request);
    }
}
