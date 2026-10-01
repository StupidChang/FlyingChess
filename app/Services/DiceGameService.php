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
            $locked = $premium && ! $isPremium && ($usingDefaults || empty($faces));
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
