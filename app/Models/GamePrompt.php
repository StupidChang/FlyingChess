<?php

namespace App\Models;

use App\Services\CardGameService;
use App\Services\DiceGameService;
use App\Services\KingGameService;
use App\Services\WhoMostLikelyService;
use Illuminate\Database\Eloquent\Model;

class GamePrompt extends Model
{
    protected $fillable = ['game', 'pool', 'is_paid', 'content', 'sort_order'];

    protected $casts = ['is_paid' => 'boolean'];

    /** 後台下拉選單與驗證共用的清單。 */
    public const GAMES = [
        'who_most_likely' => '誰最有可能',
        'card_game' => '情侶撲克牌',
        'king_game' => '國王遊戲',
        'dice_game' => '骰子遊戲',
    ];

    /**
     * 每個遊戲有哪些池。骰子是「類別.強度」,其餘是分級。
     *
     * 名稱不帶「(付費)」—— 收費是每一題自己的 is_paid,不是這一級的屬性。
     * 中度裡可以有付費題目,重度裡也可以有免費的。
     */
    /** 由輕到重的順序。升溫的階梯與後台排序都靠它。 */
    public const LEVEL_ORDER = ['mild', 'mild_plus', 'medium', 'medium_plus', 'intense'];

    public const POOLS = [
        'who_most_likely' => [
            'mild' => '輕度', 'mild_plus' => '輕中', 'medium' => '中度',
            'medium_plus' => '中重', 'intense' => '重度',
        ],
        'card_game' => [
            'mild' => '輕度', 'mild_plus' => '輕中', 'medium' => '中度',
            'medium_plus' => '中重', 'intense' => '重度',
        ],
        'king_game' => [
            'mild' => '輕度', 'mild_plus' => '輕中', 'medium' => '中度',
            'medium_plus' => '中重', 'intense' => '重度',
        ],
        'dice_game' => [
            'action.gentle' => '動作・溫和', 'action.bold' => '動作・大膽', 'action.wild' => '動作・狂野',
            'part.gentle' => '部位・溫和', 'part.bold' => '部位・大膽', 'part.wild' => '部位・狂野',
            'prop.gentle' => '道具・溫和', 'prop.wild' => '道具・狂野',
            'play.wild' => '玩法・狂野',
            'time' => '時間',
        ],
    ];

    /**
     * 某個遊戲的題庫,依池分組。
     *
     * 資料表裡沒有這個遊戲的資料時回傳空陣列 —— 由呼叫端退回程式碼裡的預設值。
     * 「空的就用預設」而不是「安裝時一定要 seed」:全新環境、測試資料庫、
     * 或有人把某個遊戲的題目全刪光,遊戲都還是能玩。
     */
    /**
     * @param  bool|null  $hasPaidAccess  null 代表不過濾(後台要看全部);
     *                                    false 只給免費題目
     */
    public static function poolsFor(string $game, ?bool $hasPaidAccess = null): array
    {
        $rows = static::where('game', $game)
            ->when($hasPaidAccess === false, fn ($q) => $q->where('is_paid', false))
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->groupBy('pool')
            ->map(fn ($items) => $items->pluck('content')->all())
            ->all();
    }

    /**
     * 把某個遊戲寫在 Service 常數裡的預設題庫匯進資料表。
     *
     * 內容的單一事實來源仍然是 Service 的常數 —— 這裡只是複製一份進來讓人能改。
     * 呼叫端要自己確認該遊戲目前是空的,不然會匯出兩份。
     */
    /** 新增與匯入時的預設值。原本掛著「(付費)」的那幾池預設收費。 */
    public static function defaultIsPaid(string $pool): bool
    {
        return $pool === 'intense' || str_ends_with($pool, '.wild');
    }

    public static function importDefaults(string $game): int
    {
        $pools = match ($game) {
            'who_most_likely' => WhoMostLikelyService::defaultPools(),
            'card_game' => CardGameService::defaultPools(),
            'king_game' => KingGameService::defaultPools(),
            'dice_game' => DiceGameService::defaultPools(),
            default => [],
        };

        $rows = [];
        $now = now();
        foreach ($pools as $pool => $items) {
            foreach (array_values($items) as $i => $content) {
                $rows[] = [
                    'game' => $game, 'pool' => $pool, 'content' => $content,
                    'is_paid' => self::defaultIsPaid($pool),
                    'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }

        if ($rows) {
            static::insert($rows);
        }

        return count($rows);
    }
}
