<?php

namespace App\Services;

use App\Models\Game;
use App\Models\TruthDareCard;
use Illuminate\Support\Str;

class TruthDareService
{
    /** 開了升溫時,每抽掉這麼多張往上開放一級。 */
    private const ESCALATE_AFTER = 4;

    public function createGame(string $playerName, string $sessionId, bool $isPrivate = false, ?int $hostUserId = null, bool $isAdult = false, string $mode = 'couple', bool $escalate = false): array
    {
        $game = Game::create([
            'code' => $this->generateCode(),
            'game_type' => 'truth_or_dare',
            'status' => 'waiting',
            'max_players' => 6,
            'is_private' => $isPrivate,
            'game_state' => [
                'current_player_index' => 0,
                'last_card_id' => null,
                'started' => false,
                'used_card_ids' => [],
                'host_user_id' => $hostUserId,
                'is_adult' => $isAdult,
                // 情侶場還是多人場。題目池差在這裡,不是差在多一個分類按鈕。
                'mode' => $mode === 'party' ? 'party' : 'couple',
                // 逐漸升溫:前幾張只出免費的曖昧題,之後才放行付費的露骨題。
                'escalate' => $escalate,
            ],
        ]);

        $game->players()->create([
            'session_id' => $sessionId,
            'player_name' => $playerName,
            'color' => 'none',
            'is_host' => true,
            'user_id' => $hostUserId,
        ]);

        return ['success' => true, 'game' => $game];
    }

    public function joinGame(Game $game, string $playerName, string $sessionId, ?int $userId = null): array
    {
        // Allow same-session re-entry (idempotent) regardless of game status
        if ($game->players()->where('session_id', $sessionId)->exists()) {
            return ['success' => true, 'message' => __('games.td_already_in_room')];
        }

        // New players can only join during waiting phase
        if (! $game->isWaiting()) {
            return ['success' => false, 'message' => __('games.td_room_started_or_ended')];
        }

        if ($game->players()->count() >= 6) {
            return ['success' => false, 'message' => __('games.td_room_full')];
        }

        $game->players()->create([
            'session_id' => $sessionId,
            'player_name' => $playerName,
            'color' => 'none',
            'is_host' => false,
            'user_id' => $userId,
        ]);

        return ['success' => true];
    }

    public function startGame(Game $game): array
    {
        if ($game->players()->count() < 1) {
            return ['success' => false, 'message' => __('games.td_need_one_player')];
        }

        $state = $game->game_state ?? [];
        $state['started'] = true;
        $state['current_player_index'] = 0;

        $game->update([
            'status' => 'playing',
            'game_state' => $state,
        ]);

        return ['success' => true];
    }

    /**
     * @param  bool  $hasPremiumContent  現在抽不抽得到付費題目 —— 房主是付費會員,
     *                                   或這台裝置在看廣告解鎖的時限內。
     */
    public function drawCard(Game $game, string $category, bool $hasPremiumContent, bool $isAdult = false): array
    {
        $state = $game->game_state ?? [];
        $usedIds = $state['used_card_ids'] ?? [];
        $mode = $state['mode'] ?? 'couple';

        $audiences = TruthDareCard::audiencesFor($mode);

        /* 付費與否是每張卡片自己的 is_paid,不是由尺度推導 —— 中度裡也會有
           想留給付費的題目。
           歷史地雷:原本這裡在 is_adult 的房間只抽 premium,而每一間房都是
           is_adult —— 結果是免費玩家照樣拿得到全部付費題目,曖昧級那批反而
           一張都抽不到,免費與付費之間根本沒有差別。 */
        $levels = $this->levelsAfterEscalation(
            $this->reachableLevels($category, $audiences, $hasPremiumContent),
            $state
        );

        $query = TruthDareCard::where('category', $category)
            ->whereIn('audience', $audiences)
            ->whereIn('level', $levels);

        if (! $hasPremiumContent) {
            $query->freeToPlay();
        }

        if (! empty($usedIds)) {
            $query->whereNotIn('id', $usedIds);
        }

        $card = $query->inRandomOrder()->first();

        if (! $card) {
            return ['success' => false, 'message' => __('games.td_no_more_cards')];
        }

        $usedIds[] = $card->id;
        $state['last_card_id'] = $card->id;
        $state['last_category'] = $category;
        $state['used_card_ids'] = $usedIds;
        $game->update(['game_state' => $state]);

        return [
            'success' => true,
            'card' => [
                'id' => $card->id,
                'category' => $card->category,
                'content' => $card->content,
                'level' => $card->level,
            ],
        ];
    }

    /**
     * 這個人在這個場合真的抽得到哪幾級,由輕到重。
     *
     * 要先問過資料庫再決定升溫的階梯 —— 免費玩家如果「升」到一個整級都是付費
     * 題目的等級,會直接抽不到東西。空的等級不進階梯,升溫就只會停在真的有題目
     * 的最高一級。
     *
     * @return string[]
     */
    private function reachableLevels(string $category, array $audiences, bool $hasPremiumContent): array
    {
        $present = TruthDareCard::where('category', $category)
            ->whereIn('audience', $audiences)
            ->when(! $hasPremiumContent, fn ($q) => $q->freeToPlay())
            ->distinct()
            ->pluck('level')
            ->all();

        return array_values(array_filter(
            TruthDareCard::LEVEL_ORDER,
            fn ($level) => in_array($level, $present, true)
        ));
    }

    /**
     * 開了「逐漸升溫」的房間,這時候開放到第幾級。
     *
     * 進度用「已經抽掉幾張」算,與前端那五個遊戲用回合數是同一個意思
     * (一輪大約就是幾張),階梯的節奏也刻意跟 public/js/escalation.js 一致。
     *
     * 拿不到的等級本來就不在 $levels 裡,所以升溫只會停在拿得到的最高一級,
     * 不會出現「升到一級之後永遠抽不到東西」。
     */
    private function levelsAfterEscalation(array $levels, array $state): array
    {
        if (empty($state['escalate']) || ! $levels) {
            return $levels;
        }

        $drawn = count($state['used_card_ids'] ?? []);
        $step = intdiv($drawn, self::ESCALATE_AFTER) + 1;

        return array_slice($levels, 0, min($step, count($levels)));
    }

    public function nextPlayer(Game $game): array
    {
        $playerCount = $game->players()->count();
        if ($playerCount === 0) {
            return ['success' => false, 'message' => __('games.td_no_players')];
        }

        $state = $game->game_state ?? [];
        $currentIndex = $state['current_player_index'] ?? 0;
        $state['current_player_index'] = ($currentIndex + 1) % $playerCount;
        $state['last_card_id'] = null;
        $state['last_category'] = null;

        $game->update(['game_state' => $state]);

        return ['success' => true, 'current_player_index' => $state['current_player_index']];
    }

    public function leaveGame(Game $game, string $sessionId): array
    {
        $player = $game->players()->where('session_id', $sessionId)->first();
        if (! $player) {
            return ['success' => false, 'message' => __('games.err_not_in_room')];
        }

        $state = $game->game_state ?? [];
        $currentIndex = $state['current_player_index'] ?? 0;
        $playerIndex = $game->players()->orderBy('id')->pluck('session_id')->search($sessionId);

        $player->delete();

        $remainingCount = $game->players()->count();

        if ($remainingCount === 0) {
            $game->update(['status' => 'finished', 'finished_at' => now()]);

            return ['success' => true, 'message' => __('games.td_room_closed')];
        }

        // Adjust current_player_index if necessary
        if ($playerIndex !== false && $playerIndex <= $currentIndex) {
            $state['current_player_index'] = $currentIndex > 0
                ? ($currentIndex - 1) % $remainingCount
                : 0;
        } else {
            $state['current_player_index'] = $currentIndex % $remainingCount;
        }

        $game->update(['game_state' => $state]);

        return ['success' => true];
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Game::where('code', $code)->exists());

        return $code;
    }
}
