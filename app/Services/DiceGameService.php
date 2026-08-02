<?php

namespace App\Services;

use App\Models\GamePrompt;
use App\Support\ContentExposure;

class DiceGameService
{
    private const ACTIONS_MILD = ['親', '摸', '吹氣', '輕咬', '愛撫', '舔'];

    private const PARTS_MILD = ['耳朵', '脖子', '臉頰', '手指', '鎖骨', '嘴唇'];

    private const ACTIONS_MEDIUM = ['深吻', '舔', '吸', '咬', '愛撫', '揉'];

    private const PARTS_MEDIUM = ['嘴唇', '脖子', '耳垂', '鎖骨', '腰', '胸口'];

    private const ACTIONS_INTENSE = ['口交', '手交', '舔弄', '吸吮', '插入', '使用玩具'];

    private const PARTS_INTENSE = ['陰莖', '陰蒂', '陰道', '肛門', '乳頭', '大腿內側'];

    private const DURATIONS = ['3 秒', '5 秒', '10 秒', '15 秒', '30 秒', '1 分鐘'];

    // 道具骰（成人情趣道具，Premium 解鎖更大膽的）
    private const PROPS_FREE = ['冰塊', '羽毛', '絲巾', '眼罩', '精油', '低溫蠟燭'];

    private const PROPS_INTENSE = ['手銬', '跳蛋', '震動棒', '按摩棒', '後庭塞', '拍子'];

    // 完整句型的玩法骰，避免「動作＋部位」自由組合出現不自然結果。
    private const PLAYS_INTENSE = [
        '口交1分鐘',
        '手指進去玩1分鐘',
        '後入30下',
        '換兩種體位',
        '玩具刺激2分鐘',
        '對方決定快慢深淺',
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
        return [
            'action.gentle' => self::ACTIONS_MILD,
            'action.bold' => self::ACTIONS_MEDIUM,
            'action.wild' => self::ACTIONS_INTENSE,
            'part.gentle' => self::PARTS_MILD,
            'part.bold' => self::PARTS_MEDIUM,
            'part.wild' => self::PARTS_INTENSE,
            'prop.gentle' => self::PROPS_FREE,
            'prop.wild' => self::PROPS_INTENSE,
            'play.wild' => self::PLAYS_INTENSE,
            'time' => self::DURATIONS,
        ];
    }

    public static function getBuiltInDice(bool $isPremium = false): array
    {
        // 後台改過就以資料表為準;沒有資料就用程式碼裡的預設。
        // 收費是每一題自己的 is_paid,所以過濾在這一層就做完了。
        $fromDb = GamePrompt::poolsFor('dice_game', $isPremium);
        $usingDefaults = empty($fromDb);
        $pools = $fromDb ?: self::defaultPools();

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
