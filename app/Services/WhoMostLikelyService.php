<?php

namespace App\Services;

use App\Models\GamePrompt;
use App\Support\ContentExposure;
use App\Support\ContentTranslations;

class WhoMostLikelyService
{
    // 內容一律成人向（情侶/派對），與 KingGameService / DiceGameService 相同：硬寫繁中，不隨語系翻譯。
    // 每一句都會接在「誰最有可能……」後面呈現。
    // mild = 曖昧調情、medium = 大膽性暗示、intense = 火辣（Premium）。

    /* 由輕到重五級。付費與否看每一題的 is_paid(匯入時 intense 預設收費);這份是資料表空的時候的預設,也是後台「匯入預設」的來源。 */
    private const DEFAULT_POOLS = [
        'mild' => [
            '在前任的限動按讚，又偷偷收回',
            '喝兩杯就開始對全場放電',
            '半夜傳「睡了嗎」，其實是想要',
            '在朋友面前裝矜持，一回家就黏上去',
            '吵架吵到一半直接親下去',
            '趁電梯只剩兩個人偷偷接吻',
            '被搭訕的時候假裝自己單身',
            '看電影看到親熱戲，偷瞄旁邊的人',
            '第一次見面就在想對方接吻技術好不好',
            '把另一半穿過的衣服偷拿回家聞',
            '約會前香水噴了三次還覺得不夠',
            '在長輩面前牽手，手指還偷偷搔對方掌心',
        ],
        'mild_plus' => [
            '在朋友家的廁所偷親到被敲門',
            '在包廂舌吻到服務生推門進來',
            '接吻時手偷偷伸進對方衣服裡',
            '在另一半脖子種草莓，隔天還得意地看',
            '偷看另一半換衣服被抓到還不承認',
            '洗澡洗到一半叫另一半進來拿毛巾',
            '喝醉之後第一個把上衣脫掉',
            '穿另一半的襯衫當睡衣，裡面什麼都沒穿',
        ],
        'medium' => [
            '在試衣間自拍內衣照傳給另一半',
            '買了性感內衣，卻一次都沒穿出來過',
            '一進門鞋還沒脫就先脫衣服',
            '在浴室裡擦槍走火',
            '被玩乳頭就全身發軟',
            '故意不穿胸罩去約會，還問對方有沒有發現',
            '聚會時在桌子底下摸另一半的大腿內側',
            '睡覺一定要摸著另一半的胸才睡得著',
            '舔乳頭舔到另一半求饒',
            '泡溫泉時故意把浴巾拉低一點',
            '傳一張只穿內褲的照片，然後秒收回',
            '光著身子在家走來走去，被外送按門鈴嚇到',
        ],
        'medium_plus' => [
            '在桌子底下摸另一半的私處，臉還裝得很正經',
            '在電影院把手伸進另一半的褲子裡',
            '自慰的時候想的是在場的某個人',
            '抽屜藏著跳蛋，還騙人說是按摩器',
            '在朋友家過夜時偷偷口交',
            '在停車場的車上幫另一半口交',
            '在朋友婚禮的飯店房間裡做愛',
            '在車上做愛做到車窗全起霧',
        ],
        'intense' => [
            '口交口到下巴痠還不肯停',
            '被口交時一定要抓著對方頭髮',
            '塞著跳蛋出門吃飯，把遙控器交給另一半',
            '出去旅行，行李有一半是情趣用品',
            '做愛做到隔壁來敲牆',
            '一邊被插一邊喊著要更用力',
            '在鏡子前面做愛，眼睛一直盯著鏡子',
            '喜歡從後面進入或被進入',
            '騎在上面自己動到腿軟',
            '一晚做三次，隔天還傳訊息說想要',
            '在飯店陽台做愛',
            '最敢開口要求試肛交',
            '被綁起來做愛反而更興奮',
            '做愛時手機響了還接起來講',
            '做完還賴在另一半身上，怎麼推都不肯下來',
            '讓對方射在臉上',
            '高潮時叫錯名字',
            '做愛時先高潮，還裝作沒有',
            '被手指弄到噴水',
            '對方高潮後還不放過他',
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

    public static function getPromptPools(bool $isPremium = false): array
    {
        // 後台改過就以資料表為準;沒有資料就用程式碼裡的預設。
        $all = GamePrompt::poolsFor('who_most_likely', $isPremium) ?: ContentTranslations::pools(self::defaultPoolsFor($isPremium));

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
