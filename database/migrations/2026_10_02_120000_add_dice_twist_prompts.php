<?php

use App\Models\GamePrompt;
use App\Services\DiceGameService;
use Illuminate\Database\Migrations\Migration;

/**
 * 骰子遊戲新增「轉折骰」(twist.bold / twist.wild)。
 *
 * 已經把骰子題庫匯進資料表的站,新的兩池要補進去,後台的題目管理才看得到、改得了。
 * 沒匯入過的站(資料表裡沒有任何骰子題目)不動 —— 那種站整個遊戲本來就讀程式碼預設。
 * 就算沒補,DiceGameService 也會把資料表裡沒有的池退回預設,不會是一顆空骰。
 */
return new class extends Migration
{
    private const POOLS = ['twist.bold', 'twist.wild'];

    public function up(): void
    {
        if (! GamePrompt::where('game', 'dice_game')->exists()) {
            return;
        }

        $now = now();
        foreach (self::POOLS as $pool) {
            if (GamePrompt::where('game', 'dice_game')->where('pool', $pool)->exists()) {
                continue;
            }
            foreach (array_values(DiceGameService::defaultPools()[$pool]) as $i => $content) {
                GamePrompt::insert([
                    'game' => 'dice_game', 'pool' => $pool, 'content' => $content,
                    'is_paid' => GamePrompt::defaultIsPaid($pool), 'sort_order' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        GamePrompt::where('game', 'dice_game')->whereIn('pool', self::POOLS)->delete();
    }
};
