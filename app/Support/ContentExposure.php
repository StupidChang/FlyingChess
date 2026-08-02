<?php

namespace App\Support;

/**
 * 一次頁面載入最多讓瀏覽器拿到多少題目。
 *
 * ── 問題 ──
 * 四個小遊戲是把整份題庫 @json 進頁面裡再由前端抽,所以「檢視原始碼」一次就能
 * 把那個等級的題目全部帶走。付費題目本來就不會送給沒權限的人(見各 Service 的
 * 過濾),但只要看一支廣告解鎖三十分鐘,整份付費題庫就進了瀏覽器 —— 一次解鎖
 * 換整份題庫,這個交換太便宜了。
 *
 * ── 做法 ──
 * 每次請求只送一份隨機子集。一場遊戲用不到幾題,所以玩家完全感覺不到差別;
 * 想蒐集整份題庫的人則要一直重載、每次拿到不同的一小把,而且付費題目每次都
 * 要重看廣告。目的不是擋死(前端拿得到的東西一定抄得走),是讓成本高到不值得。
 *
 * 上限刻意留在 config,真的遇到有人在刷可以直接調小。
 */
class ContentExposure
{
    /**
     * 把一份題目裁成隨機子集。
     *
     * 每次請求重抽,所以同一個人重載兩次拿到的不是同一批 —— 固定切前 N 筆的話
     * 等於永遠只保護後面那些,前 N 筆照樣被完整帶走。
     *
     * @param  array<int, string>  $items
     * @return array<int, string>
     */
    public static function sample(array $items): array
    {
        $cap = self::cap();

        if ($cap <= 0 || count($items) <= $cap) {
            return array_values($items);
        }

        $keys = array_rand($items, $cap);

        return array_values(array_map(fn ($k) => $items[$k], (array) $keys));
    }

    /**
     * 一整組池子(等級 => 題目陣列)一起裁。
     *
     * @param  array<string, array<int, string>>  $pools
     * @return array<string, array<int, string>>
     */
    public static function samplePools(array $pools): array
    {
        return array_map(fn ($items) => self::sample((array) $items), $pools);
    }

    public static function cap(): int
    {
        return (int) config('content.client_pool_cap', 40);
    }
}
