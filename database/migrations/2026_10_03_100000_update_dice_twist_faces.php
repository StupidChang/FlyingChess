<?php

use App\Models\GamePrompt;
use App\Services\DiceGameService;
use Illuminate\Database\Migrations\Migration;

/**
 * 轉折骰的詞條修正(2026-10-03):
 *   - 「別出聲」原本是「出聲就再加 1 分鐘」,玩法模式沒有時間骰,改成「出聲就從頭再來」
 *   - 新增四面:看著我(大膽);換姿勢、慢慢來、他喊停(狂野)—— 玩法模式原本能配的
 *     狂野轉折只剩兩種,幾乎每次都一樣
 *
 * 只動資料表裡已經有轉折骰的站;後台改過的題目(內容對不上舊字串)不碰。
 */
return new class extends Migration
{
    private const OLD = '別出聲|全程不准出聲，出聲就再加 1 分鐘';

    private const NEW = '別出聲|全程不准出聲，出聲就從頭再來';

    public function up(): void
    {
        GamePrompt::where('game', 'dice_game')->where('content', self::OLD)->update(['content' => self::NEW]);

        foreach (['twist.bold', 'twist.wild'] as $pool) {
            if (! GamePrompt::where('game', 'dice_game')->where('pool', $pool)->exists()) {
                continue;
            }
            $next = (int) GamePrompt::where('game', 'dice_game')->where('pool', $pool)->max('sort_order') + 1;
            foreach (DiceGameService::defaultPools()[$pool] as $content) {
                if (GamePrompt::where('game', 'dice_game')->where('pool', $pool)->where('content', $content)->exists()) {
                    continue;
                }
                GamePrompt::create([
                    'game' => 'dice_game', 'pool' => $pool, 'content' => $content,
                    'is_paid' => GamePrompt::defaultIsPaid($pool), 'sort_order' => $next++,
                ]);
            }
        }
    }

    public function down(): void
    {
        GamePrompt::where('game', 'dice_game')->where('content', self::NEW)->update(['content' => self::OLD]);
    }
};
