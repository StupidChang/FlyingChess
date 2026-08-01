<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * 「現在這個人可不可以玩到付費內容」的單一判斷點。
 *
 * 在這之前,五個小遊戲的控制器各自寫 `$request->user()?->isPremium() ?? false`,
 * 也就是把「帳號是付費會員」跟「現在能不能玩付費內容」當成同一件事。加入看廣告
 * 解鎖之後這兩件事分開了:訪客沒有帳號也能取得一段時間的內容權限。
 *
 * ── 界線在哪(重要)──
 * 看廣告換到的是**現在玩得到什麼**,不是帳號權益。所以它解鎖題庫、回合數,
 * 以及付費棋盤範本的「遊玩」;但刻意不影響:
 *   - 廣告本身(partials/ad-unit)—— 看廣告換到免廣告是自我矛盾
 *   - 把付費範本**存一份到自己的收藏**(BoardController::cloneTemplate)、
 *     私人房間、個人頁的完整紀錄 —— 那些是「留下來」的東西,屬於帳號
 *   - 真心話大冒險房間的題庫 —— 那是「房主的會員資格決定整間房」,
 *     用單一裝置的 session 去解鎖一間多人房會很怪
 * 上面那些仍然只看 User::isPremium()。
 *
 * 「玩得到」與「留得住」這條線,在站上沒有金流的期間特別重要:付費入口全部
 * 移除了(見 config/payments.php),沒有這條路的話付費內容等於永久鎖死。
 */
class PremiumAccess
{
    private const SESSION_KEY = 'rewarded_until';

    private const TOKEN_KEY = 'rewarded_token';

    private const TOKEN_ISSUED_KEY = 'rewarded_token_at';

    /*
     * 時間一律走 now() 而不是 PHP 的 time():專案其他地方都用 Carbon,而且
     * Laravel 的 travel() 只能撥動 Carbon —— 用 time() 的話「最短觀看秒數」
     * 這條規則在測試裡只能靠真的 sleep,等於沒辦法測。
     */

    /** 現在能不能玩到付費內容。 */
    public static function content(?User $user): bool
    {
        return ($user?->isPremium() ?? false) || self::rewardedActive();
    }

    public static function rewardedActive(): bool
    {
        return self::rewardedSecondsLeft() > 0;
    }

    public static function rewardedSecondsLeft(): int
    {
        $until = session(self::SESSION_KEY);

        return $until ? max(0, $until - now()->getTimestamp()) : 0;
    }

    public static function rewardedMinutes(): int
    {
        return max(1, (int) config('premium.rewarded.minutes', 30));
    }

    /**
     * 發一張一次性的憑證,代表「廣告開始播了」。
     *
     * ⚠ 這一段目前**不是防竄改的**:能不能領獎完全由前端回報。真正的做法是接
     * 聯播網的 server-to-server reward callback(ExoClick / TrafficJunky 都有),
     * 由對方的伺服器帶著簽章通知我們才發放。在那之前,下面的 token 與最短觀看
     * 秒數只擋得住隨手多打幾次 API 的人,擋不住真的想繞的人。
     *
     * 因為換到的只是免費內容的時限解鎖(不是錢、不是會員),這個風險是可接受的;
     * 但接上金流或提高獎勵價值之前,一定要先換成 S2S callback。
     */
    public static function issueAdToken(): string
    {
        $token = Str::random(40);

        session([
            self::TOKEN_KEY => $token,
            self::TOKEN_ISSUED_KEY => now()->getTimestamp(),
        ]);

        return $token;
    }

    /**
     * 兌換憑證。成功就把解鎖時間往後推,並回傳剩餘秒數;失敗回 0。
     */
    public static function redeem(?string $token): int
    {
        $expected = session(self::TOKEN_KEY);
        $issuedAt = (int) session(self::TOKEN_ISSUED_KEY, 0);
        $minWatch = max(1, (int) config('premium.rewarded.min_watch_seconds', 15));

        // hash_equals 而不是 ===:token 比對走定時安全比較是基本習慣。
        if (! $token || ! $expected || ! hash_equals($expected, $token)) {
            return 0;
        }

        // 廣告至少要播完最短秒數。時間戳是伺服器發的,前端改不到。
        if (now()->getTimestamp() - $issuedAt < $minWatch) {
            return 0;
        }

        // 用掉就作廢,同一張憑證不能重複領。
        session()->forget([self::TOKEN_KEY, self::TOKEN_ISSUED_KEY]);

        // 從「現在」與「原有到期時間」較晚的那個往後加,連看兩支廣告會累加
        // 而不是把剩下的時間洗掉。
        $base = max(now()->getTimestamp(), (int) session(self::SESSION_KEY, 0));
        $until = $base + self::rewardedMinutes() * 60;

        session([self::SESSION_KEY => $until]);

        return $until - now()->getTimestamp();
    }
}
