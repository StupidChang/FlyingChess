<?php

/*
 * Cloudflare 的來源 IP 範圍,給 bootstrap/app.php 的 trustProxies 用。
 *
 * 為什麼需要:網站掛在 Cloudflare 後面時,PHP 看到的連線來源是 Cloudflare 而不是
 * 使用者,而且那一段是 HTTP(Cloudflare → 主機)。沒有告訴 Laravel「這些來源是
 * 可信的代理」的話:
 *   - request()->isSecure() 會是 false,於是 url()/route() 產生的 canonical、
 *     hreflang、sitemap、llms.txt、JSON-LD 的 @id 全部變成 http:// —— 網站送的是
 *     https,SEO 訊號卻說 http,而且可能出現轉址迴圈。
 *   - 取得的使用者 IP 會全部變成 Cloudflare 的位址,節流(throttle)會把所有人
 *     算成同一個人。
 *
 * 為什麼列範圍而不是用 '*':這台主機的來源站是可以被直接連到的(preview vhost
 * 就是),用 '*' 等於任何人繞過 Cloudflare 直接打過來就能偽造 X-Forwarded-For
 * 與 X-Forwarded-Proto。列白名單之後,只有真的從 Cloudflare 來的請求算數。
 *
 * 這份清單是 15 筆 IPv4 + 7 筆 IPv6,取自官方來源:
 *   https://www.cloudflare.com/ips-v4  /  https://www.cloudflare.com/ips-v6
 * Cloudflare 很少改,但確實會改。改了而沒更新這裡的症狀是「網站看起來正常,
 * 但 canonical 變回 http、而且所有人的 IP 都一樣」—— 沒有錯誤訊息,只有 SEO
 * 慢慢變差,所以值得偶爾對一次。
 */
return [
    'proxies' => [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],
];
