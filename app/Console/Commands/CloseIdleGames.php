<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

/**
 * 把閒置太久的場次收掉。
 *
 * 真心話大冒險只有在「所有人都按離開」時才會結束,飛行棋只有走到終點才會結束 ——
 * 可是大家通常玩到一半就直接關掉分頁,於是後台的場次永遠停在「進行中」,
 * 看不出現在到底有沒有人在玩。
 *
 * 判斷依據是 updated_at:抽牌、擲骰、移動、加入都會寫 game_state 或 status,
 * 所以它就是「最後一次有人動作」的時間。純輪詢不會寫入,不算動作。
 */
class CloseIdleGames extends Command
{
    protected $signature = 'games:close-idle {--hours= : 閒置幾小時就關閉(預設 Game::IDLE_CLOSE_HOURS)}';

    protected $description = 'Close waiting/playing games that have had no activity for a while';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?: Game::IDLE_CLOSE_HOURS);

        $closed = Game::whereIn('status', ['waiting', 'playing'])
            ->where('updated_at', '<', now()->subHours($hours))
            ->update([
                'status' => Game::STATUS_ABANDONED,
                'finished_at' => now(),
            ]);

        $this->info("Closed {$closed} idle game(s) (no activity for {$hours}h).");

        return self::SUCCESS;
    }
}
