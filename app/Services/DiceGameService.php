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
            '看著我|全程看著對方的眼睛，移開視線就重來',
        ],
        'twist.wild' => [
            '換嘴|做到一半換成用嘴',
            '口交|做完直接幫對方口交 30 秒',
            '別出聲|全程不准出聲，出聲就從頭再來',
            '他指定|對方可以再指定一個部位，一起弄',
            '脫光|兩個人都脫光再做',
            '騎上去|做完騎上去自己動 20 下',
            '換姿勢|做到一半換一個姿勢',
            '慢慢來|全程只能慢慢來，誰先加快就重來',
            '他喊停|對方喊停就要停，喊開始才能繼續',
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

    /*
     * 組合規則:擲骰時只挑說得通的組合(「吸 鎖骨」「用羽毛舔」「輕咬 2 分鐘」不會出現)。
     *
     * 鍵是**繁中原文**(跟翻譯字典同一套),所以四個語系共用;骰面傳到前端時帶著原文
     * (keys),前端照這份表過濾。後台自己加的骰面對不到規則,就不受限制。
     * 前端的挑法是:隨機抽一組 → 檢查 → 不合就重抽;真的抽不到全合的組合時,
     * 退而取違規最少的那一組,不會卡住。見 dice-game/show 的 pickCombo()。
     */
    private const MOUTH = ['親', '輕咬', '舔', '吸', '用舌尖逗', '邊吸邊舔', '邊舔邊揉'];

    private const RULES = [
        // 用嘴的動作(「換成用嘴」這個轉折不會配到它們)
        'mouth' => self::MOUTH,
        // 一下子的動作,時間不超過 30 秒
        'quick' => ['輕咬', '吸', '用鼻尖蹭'],
        'quick_max' => 30,
        // 部位 => 配不上的動作
        'part_deny' => [
            '耳垂' => ['揉', '磨蹭', '撫摸', '用手指玩', '用跳蛋震', '邊舔邊揉'],
            '脖子' => ['磨蹭', '用手指玩', '用跳蛋震'],
            '鎖骨' => ['吸', '揉', '磨蹭', '用手指玩', '用跳蛋震', '邊吸邊舔', '邊舔邊揉'],
            '嘴唇' => ['揉', '磨蹭', '用跳蛋震'],
            '手指' => ['揉', '磨蹭', '撫摸', '用手指玩', '用跳蛋震'],
            '腰' => ['吸', '邊吸邊舔', '用手指玩', '用跳蛋震'],
            '臀部' => ['吸', '用舌尖逗', '邊吸邊舔'],
            '下腹' => ['吸', '邊吸邊舔'],
            '隔著內褲的私處' => ['吸', '邊吸邊舔'],
            '會陰' => ['吸'],
        ],
        // 道具 => 配不上的動作(眼罩、絲巾、手銬、保險套是「戴著做」的,配什麼都行)
        'prop_deny' => [
            '冰塊' => ['輕咬', '用鼻尖蹭', '用跳蛋震', '用手指玩'],
            '羽毛' => ['親', '輕咬', '舔', '吸', '用舌尖逗', '邊吸邊舔', '邊舔邊揉', '揉', '用跳蛋震', '用手指玩', '用鼻尖蹭'],
            '按摩油' => [...self::MOUTH, '用鼻尖蹭', '用跳蛋震'],
            '溫熱毛巾' => [...self::MOUTH, '用鼻尖蹭', '用跳蛋震', '用手指玩', '磨蹭'],
            // 玩具:動作本身已經有工具(用跳蛋震、用手指玩)的話,不會再多拿一個玩具
            '跳蛋' => ['親', '輕咬', '舔', '吸', '用舌尖逗', '邊吸邊舔', '用鼻尖蹭', '用跳蛋震', '用手指玩', '邊舔邊揉'],
            '震動棒' => ['親', '輕咬', '舔', '吸', '用舌尖逗', '邊吸邊舔', '用鼻尖蹭', '用跳蛋震', '用手指玩', '邊舔邊揉'],
            '按摩棒' => ['親', '輕咬', '舔', '吸', '用舌尖逗', '邊吸邊舔', '用鼻尖蹭', '用跳蛋震', '用手指玩', '邊舔邊揉'],
            '潤滑液' => ['親', '輕咬', '吸', '用鼻尖蹭', '邊吸邊舔'],
        ],
        // 只配玩法骰的道具(保險套跟「舔」「揉」放在一起說不通)
        'prop_needs_play' => ['保險套'],
        // 道具 => 配不上的玩法
        'prop_play_deny' => [
            '保險套' => ['幫對方口交1分鐘', '69互舔1分鐘', '用手指弄對方，快慢聽他的'],
            '潤滑液' => ['幫對方口交1分鐘', '69互舔1分鐘'],
        ],
        // 轉折 => 需要的條件。time:桌上要有時間骰;part:要有部位骰;
        // not_mouth:動作本來就是用嘴的不行;no_play:「再做另一件事」的轉折不配在玩法骰上
        // (玩法本身就是一整件事,再疊一件就做不完,還會跟玩法重複:口交配口交、騎上去配騎上去)。
        // 狂野轉折另外要求桌上至少一顆「大膽」以上(前端)。
        'twist_needs' => [
            '時間×2|時間加倍' => ['time'],
            '換嘴|做到一半換成用嘴' => ['not_mouth', 'no_play'],
            '他指定|對方可以再指定一個部位，一起弄' => ['part'],
            '口交|做完直接幫對方口交 30 秒' => ['no_play'],
            '騎上去|做完騎上去自己動 20 下' => ['no_play'],
            '加碼|再擲一次，兩次的結果都要做' => ['no_play'],
            // 嘴巴在忙的時候說不了話
            '說出來|做的時候一直說你有多想要，停下來就重來' => ['not_mouth'],
            // 這一輪本來就在口交(用嘴的動作 + 私處)的話,做完再口交是重複
            '口交|做完直接幫對方口交 30 秒' => ['no_play', 'not_oral_now'],
            // 換姿勢是換體位:只配插入類的玩法(其他玩法由 twist_deny 排除)
            '換姿勢|做到一半換一個姿勢' => ['play'],
        ],
        // 會被當成「私處」的部位(not_oral_now 用)
        'genital' => ['私處', '陰蒂或龜頭', '會陰', '隔著內褲的私處'],
        // 轉折 => 桌上不能同時出現的骰面(任何類別)
        'twist_deny' => [
            // 道具已經是眼罩／絲巾就不用再蒙一次
            '蒙眼|被做的人蒙上眼睛，不准偷看' => ['眼罩', '絲巾'],
            // 看不到眼睛的情況:蒙著眼、69、後入
            '看著我|全程看著對方的眼睛，移開視線就重來' => ['眼罩', '絲巾', '69互舔1分鐘', '後入抽插30下'],
            // 嘴巴不該沾到按摩油、潤滑液
            '換嘴|做到一半換成用嘴' => ['按摩油', '潤滑液'],
            // 都脫光了就沒有「隔著內褲」
            '脫光|兩個人都脫光再做' => ['隔著內褲的私處'],
            // 玩法本身就是換體位
            '換姿勢|做到一半換一個姿勢' => ['換兩種體位各做1分鐘', '幫對方口交1分鐘', '69互舔1分鐘', '用手指弄對方，快慢聽他的'],
        ],
        // 玩法骰單獨上桌:開了它,這幾類就關掉(前端的骰子設定照這份做)
        'play_excludes' => ['action', 'part', 'time'],
    ];

    public static function rules(): array
    {
        return self::RULES;
    }

    /** 程式碼裡的預設骰面,鍵是「類別.強度」(時間骰沒有強度)。 */
    public static function defaultPools(): array
    {
        return self::DEFAULT_POOLS;
    }

    public static function getBuiltInDice(bool $isPremium = false): array
    {
        // 後台改過就以資料表為準;沒有資料就用程式碼裡的預設。
        // 收費是每一題自己的 is_paid,所以過濾在這一層就做完了。
        // 先拿繁中原文:組合規則用原文對,翻譯放到最後(見 RULES)
        $fromDb = GamePrompt::rawPoolsFor('dice_game', $isPremium);
        $usingDefaults = empty($fromDb);
        $pools = $fromDb ?: self::defaultPools();

        /* 資料表裡有這個遊戲、但沒有某一池(之後才加的骰子,例如轉折骰)時,那一池退回
           程式碼預設 —— 不然新骰子在已經匯入過題庫的站上會是一顆空骰。
           退回預設的付費池沒有 is_paid 可言,沒有付費權限就照舊整顆鎖住(見下面)。 */
        $fallbackPools = [];
        if (! $usingDefaults) {
            // 用「資料表裡有沒有這一池」判斷,不是看過濾後的結果 —— 沒有付費權限時,
            // 付費題目已經被濾掉了,那一池看起來是空的,但不代表要退回預設
            $poolsInDb = GamePrompt::where('game', 'dice_game')->distinct()->pluck('pool')->flip();
            foreach (self::defaultPools() as $key => $faces) {
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
            // keys 是繁中原文(組合規則用),faces 是同一批翻成目前語系,兩者一一對應
            $keys = $locked ? [] : ContentExposure::sample(array_values(array_unique($faces)));
            $out[] = [
                'id' => 'builtin_'.$cat.($intensity ? '_'.$intensity : ''),
                'cat' => $cat,
                'intensity' => $intensity,
                'premium' => $premium,
                'locked' => $locked,
                'custom' => false,
                'keys' => $keys,
                'faces' => array_map(fn ($t) => ContentTranslations::translate($t), $keys),
            ];
        }

        return $out;
    }
}
