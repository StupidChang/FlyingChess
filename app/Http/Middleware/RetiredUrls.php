<?php

namespace App\Http\Middleware;

use App\Support\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 這個網域的前一手是一間 Shopify 商店,搜尋引擎的索引裡到現在還留著它的網址
 * (`/products/…`、`/collections/…`、`/sq/collections/all?page=4`)。
 *
 * 2026-08-21 的日誌:Googlebot 仍在抓那些網址,而它們目前的下場是
 * 301 到 `/tw/products/…` 再 404 —— 兩跳、而且 404 對搜尋引擎的意思是
 * 「暫時找不到,晚點再來」。所以那些網址會一直被重抓,而在搜尋結果裡
 * 「pillownight.com」也一直是一間賣枕頭的店。
 *
 * 410 Gone 的意思是「這裡永久沒有了」,Google 的文件說得很清楚:410 會比 404
 * 更快從索引移除。這一支就只做這件事,而且刻意排在整條中介層最前面 ——
 * 排在語系轉址之後的話,拿到的會是 301,根本走不到這裡。
 *
 * 這**不是**清理使用者可能收藏的舊網址:本站從來沒有 /products 或 /collections
 * 這種路徑,命中的一定是前一手留下的。
 */
class RetiredUrls
{
    /**
     * 前一手商店的網址空間。Shopify 的固定路徑,本站一個都沒用到。
     *
     * 語系前綴會先被剝掉,所以 `/products/x` 與 `/tw/products/x` 都會命中 ——
     * 後者是我們自己的轉址造出來的,同一批網址的第二跳。
     */
    private const RETIRED_PATTERNS = [
        '#^products(/|$)#',
        '#^collections(/|$)#',
        '#^pages(/|$)#',
        '#^blogs(/|$)#',
        '#^policies(/|$)#',
        '#^cart(/|$)#',
        '#^checkout(/|$)#',
        '#^account(/|$)#',
        '#^apps(/|$)#',
        '#^recommendations(/|$)#',
        /* Shopify 的語系前綴 + 商店路徑。日誌裡實際出現過 /sq/collections 與
           /nl/products —— 兩個字母的語系碼不能單獨當條件(會撞到我們自己的
           en/jp),所以一定要連著商店路徑一起比對。 */
        '#^[a-z]{2}(-[a-z]{2})?/(products|collections|pages|blogs|policies|cart|checkout|account)(/|$)#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = LocaleHelper::stripLocalePrefix(ltrim($request->path(), '/'));

        foreach (self::RETIRED_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return response('410 Gone', 410, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
        }

        return $next($request);
    }
}
