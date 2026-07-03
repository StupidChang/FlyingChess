<?php

namespace App\Services;

use App\Models\Game;
use App\Models\TruthDareCard;
use Illuminate\Support\Str;

class TruthDareService
{
    public function createGame(string $playerName, string $sessionId, bool $isPrivate = false, ?int $hostUserId = null, bool $isAdult = false): array
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

    public function drawCard(Game $game, string $category, bool $hostIsPremium, bool $isAdult = false): array
    {
        if ($isAdult) {
            // 18+ mode: only show adult (premium) cards
            $tiers = ['premium'];
        } else {
            $tiers = ['free'];
            if ($hostIsPremium) {
                $tiers[] = 'premium';
            }
        }

        $state = $game->game_state ?? [];
        $usedIds = $state['used_card_ids'] ?? [];

        $query = TruthDareCard::where('category', $category)
            ->whereIn('tier', $tiers);

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
                'tier' => $card->tier,
            ],
        ];
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
            $game->update(['status' => 'finished']);

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
