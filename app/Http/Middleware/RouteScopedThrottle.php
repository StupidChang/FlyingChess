<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use RuntimeException;

/**
 * `throttle:60,1` 的計數器改成「這條路由 + 這個人」各自算。
 *
 * Laravel 原本的匿名寫法 key 只有網域 + IP(見 ThrottleRequests::
 * resolveRequestSignature),等於全站所有 `throttle:N,1` 共用一個計數器:
 * 飛行棋每 2 秒輪詢一次 state,同一個 Wi-Fi 底下的另一個人去開分享棋盤就被 429;
 * Googlebot 照 sitemap 爬到第 60 個有節流的網址,後面的全部 429。
 * 2026-10-01 上線前的全站檢查實際爬到 46 個 429。
 *
 * uri() 是路由樣板(`{locale}/games/{code}/state`),所以同一條路由跨語系、
 * 跨房號仍然共用一個 bucket —— 節流原本要擋的「同一件事做太多次」沒有變寬。
 * 具名節流器(throttle:minigame-page 之類)走另一條路,不受這裡影響。
 */
class RouteScopedThrottle extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        if (! $route) {
            throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
        }

        $who = ($user = $request->user()) ? 'u:'.$user->getAuthIdentifier() : 'ip:'.$request->ip();

        return sha1(implode('|', [
            $route->getDomain(), implode(',', $route->methods()), $route->uri(), $who,
        ]));
    }
}
