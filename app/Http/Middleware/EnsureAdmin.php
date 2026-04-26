<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '需要管理員權限。',
                ], 403);
            }

            abort(403, '需要管理員權限。');
        }

        return $next($request);
    }
}
