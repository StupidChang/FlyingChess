<?php

namespace App\Services;

use App\Models\GamePrompt;
use App\Support\ContentExposure;
use App\Support\ContentTranslations;

class DiceGameService
{
    /* 預設骰面,鍵是「類別.強度」(時間骰沒有強度)。gentle／bold 免費,wild 付費(見 GamePrompt::defaultIsPaid)。 */
    private const DEFAULT_POOLS = [
        'action.gentle' => [
            '親',
            '輕咬',
            '舔',
            '輕撫',
            '吸',
            '用鼻尖蹭',
        ],
        'part.gentle' => [
            '耳垂',
            '脖子',
            '鎖骨',
            '嘴唇',
            '手指',
            '腰',
        ],
        'prop.gentle' => [
            '冰塊',
            '羽毛',
            '絲巾',
            '眼罩',
            '按摩油',
            '溫熱毛巾',
        ],
        'action.bold' => [
            '舔',
            '吸',
            '揉',
            '撫摸',
            '輕咬',
            '磨蹭',
        ],
        'part.bold' => [
            '乳頭',
            '胸部',
            '大腿內側',
            '臀部',
            '下腹',
            '隔著內褲的私處',
        ],
        'action.wild' => [
            '舔',
            '用舌尖逗',
            '邊吸邊舔',
            '邊舔邊揉',
            '用手指玩',
            '用跳蛋震',
        ],
        'part.wild' => [
            '私處',
            '陰蒂或龜頭',
            '乳頭',
            '會陰',
            '大腿根',
            '最敏感的點',
        ],
        'prop.wild' => [
            '跳蛋',
            '震動棒',
            '按摩棒',
            '潤滑液',
            '手銬',
            '保險套',
        ],
        'play.wild' => [
            '幫對方口交1分鐘',
            '用手指弄對方，快慢聽他的',
            '69互舔1分鐘',
            '騎上去自己動30下',
            '後入抽插30下',
            '換兩種體位各做1分鐘',
        ],
        /* 轉折骰:讓一輪不只是「做一個動作」。每一面寫成「骰面上的短標|擲到之後的完整說明」——
           骰面只有 80px,放不下一整句;擲完的結果卡片才顯示完整的那一句(見 dice-game/show)。
           沒有「|」的面就整句都是短標也是說明。 */
        'twist.bold' => [
            '反過來|反過來，換對方照這個做給你',
            '時間×2|時間加倍',
            '猜拳|先猜拳，輸的人脫一件再開始',
            '蒙眼|被做的人蒙上眼睛，不准偷看',
            '說出來|做的時候一直說你有多想要，停下來就重來',
            '加碼|再擲一次，兩次的結果都要做',
        ],
        'twist.wild' => [
            '換嘴|做到一半換成用嘴',
            '口交|做完直接幫對方口交 30 秒',
            '別出聲|全程不准出聲，出聲就再加 1 分鐘',
            '他指定|對方可以再指定一個部位，一起弄',
            '脫光|兩個人都脫光再做',
            '騎上去|做完騎上去自己動 20 下',
        ],
        'time' => [
            '10秒',
            '20秒',
            '30秒',
            '45秒',
            '1分鐘',
            '2分鐘',
        ],
    ];

    /**
     * Built-in dice as a flat list. Each category (action / part / prop) offers
     * three intensity variants — 溫柔 gentle / 大膽 bold / 狂野 wild — that the
     * player picks from; 狂野 (wild) is premium-only. Time has a single die.
     *
     * `faces` is omitted (empty) for premium dice a non-premium user can't use,
     * so paid content never ships to the client. Each entry:
     *   id, cat, intensity(null|gentle|bold|wild), premium(bool), locked(bool), faces[]
     */
    /* 有哪些骰子、各自的類別與強度。內容(骰面)另外從資料表或預設值拿 ——
       這裡只描述結構,改題目不該動到這份清單。 */
    private const DICE_DEFS = [
        ['action', 'gentle', false],
        ['action', 'bold',   false],
        ['action', 'wild',   true],
        ['part',   'gentle', false],
        ['part',   'bold',   false],
        ['part',   'wild',   true],
        ['prop',   'gentle', false],
        ['prop',   'wild',   true],
        ['play',   'wild',   true],
        ['twist',  'bold',   false],
        ['twist',  'wild',   true],
        ['time',   null,     false],
    ];

    /** 程式碼裡的預設骰面,鍵是「類別.強度」(時間骰沒有強度)。 */
    public static function defaultPools(): array
    {
        return self::DEFAULT_POOLS;
    }

    public static function getBuiltInDice(bool $isPremium = false): array
    {
        // 後台改過就以資料表為準;沒有資料就用程式碼裡的預設。
        // 收費是每一題自己的 is_paid,所以過濾在這一層就做完了。
        $fromDb = GamePrompt::poolsFor('dice_game', $isPremium);
        $usingDefaults = empty($fromDb);
        $pools = $fromDb ?: ContentTranslations::pools(self::defaultPools());

        /* 資料表裡有這個遊戲、但沒有某一池(之後才加的骰子,例如轉折骰)時,那一池退回
           程式碼預設 —— 不然新骰子在已經匯入過題庫的站上會是一顆空骰。
           退回預設的付費池沒有 is_paid 可言,沒有付費權限就照舊整顆鎖住(見下面)。 */
        $fallbackPools = [];
        if (! $usingDefaults) {
            // 用「資料表裡有沒有這一池」判斷,不是看過濾後的結果 —— 沒有付費權限時,
            // 付費題目已經被濾掉了,那一池看起來是空的,但不代表要退回預設
            $poolsInDb = GamePrompt::where('game', 'dice_game')->distinct()->pluck('pool')->flip();
            foreach (ContentTranslations::pools(self::defaultPools()) as $key => $faces) {
                if (! isset($poolsInDb[$key])) {
                    $pools[$key] = $faces;
                    $fallbackPools[$key] = true;
                }
            }
        }

        $defs = [];
        foreach (self::DICE_DEFS as [$cat, $intensity, $premium]) {
            $key = $cat.($intensity ? '.'.$intensity : '');
            $defs[] = [$cat, $intensity, $premium, $pools[$key] ?? []];
        }

        $out = [];
        foreach ($defs as [$cat, $intensity, $premium, $faces]) {
            /* 付費骰只有在「一面都不剩」的時候才鎖起來 —— 管理員把狂野裡的幾題
               設成免費之後,那顆骰子就該玩得到,整顆鎖掉的話那些題目等於白設。
               但退回程式碼預設題庫時沒有 is_paid 可言,那就照舊整顆鎖住。 */
            $key = $cat.($intensity ? '.'.$intensity : '');
            $locked = $premium && ! $isPremium && ($usingDefaults || isset($fallbackPools[$key]) || empty($faces));
            if ($locked) {
                $faces = [];
            }
            $out[] = [
                'id' => 'builtin_'.$cat.($intensity ? '_'.$intensity : ''),
                'cat' => $cat,
                'intensity' => $intensity,
                'premium' => $premium,
                'locked' => $locked,
                'custom' => false,
                'faces' => $locked ? [] : ContentExposure::sample(array_values(array_unique($faces))),
            ];
        }

        return $out;
    }
}
