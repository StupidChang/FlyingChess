<?php

namespace App\Services;

use App\Models\GamePrompt;
use App\Support\ContentExposure;
use App\Support\ContentTranslations;

class KingGameService
{
    /* 由輕到重五級。付費與否看每一題的 is_paid(匯入時 intense 預設收費);這份是資料表空的時候的預設,也是後台「匯入預設」的來源。 */
    private const DEFAULT_POOLS = [
        'mild' => [
            '{A} 在 {B} 耳邊吹一口氣，再說一句最撩的話',
            '{A} 和 {B} 咬住同一根餅乾棒從兩頭吃，先咬斷的人喝一杯',
            '{A} 盯著 {B} 看，其他人一起數到 15，先笑的人被親一下',
            '{A} 從背後抱住 {B}，在他耳邊說今晚想怎麼欺負他',
            '{A} 閉上眼睛，{B} 親他身上一個地方，猜錯的人喝一杯',
            '{A} 把 {B} 輕輕壓到牆上對視，沒被點到的人喊停才能放開',
            '{A} 含住 {B} 的一根手指，其他人慢慢數 5 下',
            '{A} 含一口酒，嘴對嘴餵給 {B}，灑出來就再來一口',
        ],
        'mild_plus' => [
            '{A} 和 {B} 舌吻，其他人數到 20 才可以分開',
            '{A} 在 {B} 的脖子種一顆草莓，大家檢查夠不夠紅',
            '{A} 把手伸進 {B} 的衣服裡，貼著皮膚從背摸到腰',
            '{A} 跨坐在 {B} 腿上面對面磨，先喊停的人脫一件',
            '{A} 只用嘴幫 {B} 脫掉一件，外套襪子也算',
            '{A} 咬住 {B} 的下唇 3 秒，再一路親到鎖骨',
        ],
        'medium' => [
            '{A} 幫 {B} 脫到只剩內衣褲，其他人負責吹口哨',
            '{A} 和 {B} 猜拳，輸一次脫一件，脫到內褲為止',
            '{A} 沿著 {B} 裸露的背，從後頸一路親到腰',
            '{A} 把手伸進 {B} 的內衣裡，揉胸揉到他出聲',
            '{A} 用嘴含住 {B} 的乳頭，大家一起數 20 秒',
            '{A} 拿冰塊從 {B} 的鎖骨滑到胸口，冰塊掉了就用舌頭撿',
            '{A} 蒙住 {B} 的眼睛捏他一邊乳頭，猜錯左右就脫一件',
            '{A} 拉開上衣讓 {B} 舔乳頭，舔哪一邊由沒被點到的人選',
        ],
        'medium_plus' => [
            '{A} 隔著內褲揉 {B} 的私處，揉到濕了或硬了才停',
            '{A} 幫 {B} 口交，其他人一起倒數 30 秒',
            '{A} 把一根手指伸進 {B} 的私處，快慢由旁邊的人喊',
            '{A} 和 {B} 躺下來 69，先鬆口的人喝一杯',
            '{A} 戴好保險套，和 {B} 用傳教士插 5 下就停',
            '{A} 讓 {B} 騎上來只能動 10 下，其他人數出聲',
        ],
        'intense' => [
            '{A} 幫 {B} 口交 1 分鐘，兩隻手要放在背後',
            '{A} 跪著幫 {B} 口交，眼睛離開他就重來',
            '{A} 用手指幫 {B}，快慢深淺由沒被點到的人喊',
            '{A} 在大家面前和 {B} 69，先高潮的人輸',
            '{A} 拿跳蛋貼著 {B} 的陰蒂或龜頭，震到他求饒',
            '{A} 和 {B} 用傳教士做 1 分鐘，其他人圍著看',
            '{A} 從後面插 {B}，他每被插一下都要說「還要」',
            '{A} 騎在 {B} 身上，自己決定吃到多深，動 30 下',
            '{A} 打開房門，把 {B} 壓在門邊站著插 30 秒',
            '{A} 和 {B} 插進去後停住，誰先忍不住動誰輸',
            '{A} 和 {B} 側躺從後面來，慢慢做 2 分鐘',
            '{A} 和 {B} 做愛，其他人喊一次「換」就換一種體位',
            '{A} 一邊和 {B} 做愛，一邊用跳蛋震他前面',
            '{A} 和 {B} 做到有人高潮，大家先押誰先到，押錯的喝',
            '{A} 把 {B} 弄到高潮為止，用什麼方法都可以',
            '{A} 先用嘴把 {B} 弄到快不行，再直接騎上去',
        ],
    ];

    /** 程式碼裡的預設題庫。資料表空的時候用它,也是後台第一次匯入的來源。 */
    /** 資料表空的時候用的預設。沒有 is_paid 可言,所以照舊整級判斷。 */
    private static function defaultPoolsFor(bool $isPremium): array
    {
        $all = self::defaultPools();

        // 預設題庫沒有 is_paid 可言,照舊把原本標著付費的那級留給有權限的人。
        if (! $isPremium) {
            unset($all['intense']);
        }

        return $all;
    }

    public static function defaultPools(): array
    {
        return self::DEFAULT_POOLS;
    }

    public static function getCommandPools(bool $isPremium = false): array
    {
        $all = GamePrompt::poolsFor('king_game', $isPremium) ?: ContentTranslations::pools(self::defaultPoolsFor($isPremium));

        /* 收費是每一題自己的 is_paid,所以過濾在 poolsFor 那一層做完了 ——
           這裡不再整級砍掉 intense:重度裡也可以有免費題目。
           程式碼裡的預設題庫沒有 is_paid 這個概念,所以退回預設時仍然照舊
           把 intense 留給有權限的人。 */
        $pools = [];
        foreach (GamePrompt::LEVEL_ORDER as $level) {
            if (! empty($all[$level])) {
                $pools[$level] = $all[$level];
            }
        }

        // 一次只送一份隨機子集,見 ContentExposure 的說明
        return ContentExposure::samplePools($pools);
    }
}
