<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 一個已經按過「我已滿 18 歲」的訪客。
     *
     * AgeVerification 在 web 群組裡,比路由中介層更早跑,所以沒帶這個 cookie 的
     * 請求拿到的是年齡閘 —— 而且它回 200,只斷言狀態碼會過,但根本沒碰到要測的
     * 東西(GET 會拿到覆蓋層底下的頁面,POST 會直接被擋)。
     *
     * 以前的測試是用 Googlebot 的 UA 繞過去的。那在年齡閘改成覆蓋層之後就不再
     * 成立(讀取型請求本來就全部放行,寫入型則連爬蟲也擋),而且那本來就是在
     * 模擬一個不存在的情境:Googlebot 不會 POST。要模擬「一個確認過年齡的人」,
     * 就給它那個 cookie。
     */
    protected function asAgeVerified()
    {
        /* withCredentials() 是必要的,不是保險:Laravel 的
           prepareCookiesForJsonRequest() 在沒有它的時候回傳空陣列 —— 也就是
           postJson()/getJson() 這類請求**一顆 cookie 都不會送**。少了它,
           JSON 端點的測試會拿到年齡閘的 HTML,然後在「Invalid JSON」那裡失敗,
           錯誤訊息完全看不出來跟 cookie 有關。

           cookie 本身用不加密的:年齡閘由全域中介層處理,跑在 EncryptCookies
           之前,讀到的是原始值。 */
        return $this->withCredentials()->withUnencryptedCookie('age_verified', '1');
    }
}
