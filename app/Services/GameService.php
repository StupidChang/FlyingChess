<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GamePlayer;
use Illuminate\Support\Str;

class GameService
{
    // Color turn order
    const COLORS = ['yellow', 'blue', 'green', 'red'];

    // Each color's absolute starting index on the 52-square main track
    const START_OFFSETS = [
        'yellow' => 0,
        'blue' => 13,
        'green' => 26,
        'red' => 39,
    ];

    // The absolute track index that is each color's "safe-lane entry gate"
    // (piece is at this square when relative progress = 52)
    const SAFE_ENTRY = [
        'yellow' => 51,   // track[51] = (7,0)
        'blue' => 12,   // track[12] = (0,7)
        'green' => 25,   // track[25] = (7,14)
        'red' => 38,   // track[38] = (14,7)
    ];

    // Relative progress limits
    const MAX_PROGRESS = 58; // 58 = finished at center

    const SAFE_LANE_START = 53;

    // 52 main-track squares [row, col] going clockwise
    const BOARD_TRACK = [
        [6, 0], [6, 1], [6, 2], [6, 3], [6, 4], [6, 5],       // 0-5
        [5, 6], [4, 6], [3, 6], [2, 6], [1, 6], [0, 6],       // 6-11
        [0, 7], [0, 8],                                // 12-13
        [1, 8], [2, 8], [3, 8], [4, 8], [5, 8],              // 14-18
        [6, 9], [6, 10], [6, 11], [6, 12], [6, 13], [6, 14],  // 19-24
        [7, 14], [8, 14],                              // 25-26
        [8, 13], [8, 12], [8, 11], [8, 10], [8, 9],          // 27-31
        [9, 8], [10, 8], [11, 8], [12, 8], [13, 8], [14, 8],  // 32-37
        [14, 7], [14, 6],                              // 38-39
        [13, 6], [12, 6], [11, 6], [10, 6], [9, 6],          // 40-44
        [8, 5], [8, 4], [8, 3], [8, 2], [8, 1], [8, 0],        // 45-50
        [7, 0],                                      // 51
    ];

    // Safe-lane squares [row,col] for each color (5 squares, index 0=first)
    const SAFE_LANES = [
        'yellow' => [[7, 1], [7, 2], [7, 3], [7, 4], [7, 5]],
        'blue' => [[1, 7], [2, 7], [3, 7], [4, 7], [5, 7]],
        'green' => [[7, 13], [7, 12], [7, 11], [7, 10], [7, 9]],
        'red' => [[13, 7], [12, 7], [11, 7], [10, 7], [9, 7]],
    ];

    // Center finishing cell
    const CENTER = [7, 7];

    // Home piece display positions [row,col] for the 4 pieces of each color
    const HOME_POSITIONS = [
        'yellow' => [[1, 1], [1, 3], [3, 1], [3, 3]],
        'blue' => [[1, 10], [1, 12], [3, 10], [3, 12]],
        'green' => [[10, 10], [10, 12], [12, 10], [12, 12]],
        'red' => [[10, 1], [10, 3], [12, 1], [12, 3]],
    ];

    // Safe (star) squares on the main track (absolute indices) - cannot be captured
    const SAFE_SQUARES = [0, 8, 13, 21, 26, 34, 39, 47];

    public function createGame(string $playerName, int $maxPlayers, string $sessionId, bool $solo = false, ?int $userId = null): array
    {
        $code = $this->generateCode();
        $bots = $solo ? ['blue', 'green', 'red'] : [];

        $game = Game::create([
            'code' => $code,
            'status' => $solo ? 'playing' : 'waiting',
            'max_players' => $solo ? 4 : $maxPlayers,
            'game_state' => $this->initialState(['yellow', ...$bots], $bots),
        ]);

        $player = GamePlayer::create([
            'game_id' => $game->id,
            'session_id' => $sessionId,
            'player_name' => $playerName,
            'color' => 'yellow',
            'is_host' => true,
            'user_id' => $userId,
        ]);

        // Create bot player records
        foreach ($bots as $botColor) {
            GamePlayer::create([
                'game_id' => $game->id,
                'session_id' => 'bot_'.$botColor,
                'player_name' => 'AI ('.strtoupper($botColor[0]).')',
                'color' => $botColor,
                'is_host' => false,
            ]);
        }

        return ['game' => $game, 'player' => $player];
    }

    public function joinGame(Game $game, string $playerName, string $sessionId, ?int $userId = null): array
    {
        // Check already in game
        $existing = $game->players()->where('session_id', $sessionId)->first();
        if ($existing) {
            return ['success' => true, 'player' => $existing, 'game' => $game];
        }

        if (! $game->isWaiting()) {
            return ['success' => false, 'message' => '遊戲已經開始或結束'];
        }

        if ($game->isFull()) {
            return ['success' => false, 'message' => '房間已滿'];
        }

        $takenColors = $game->players()->pluck('color')->toArray();
        $available = array_diff(self::COLORS, $takenColors);
        $color = array_values($available)[0];

        $player = GamePlayer::create([
            'game_id' => $game->id,
            'session_id' => $sessionId,
            'player_name' => $playerName,
            'color' => $color,
            'is_host' => false,
            'user_id' => $userId,
        ]);

        return ['success' => true, 'player' => $player, 'game' => $game];
    }

    public function startGame(Game $game): array
    {
        $playerCount = $game->players()->count();
        $hasBots = ! empty($game->game_state['bots'] ?? []);

        if (! $hasBots && $playerCount < 2) {
            return ['success' => false, 'message' => '至少需要2位玩家才能開始'];
        }

        $colors = $game->players()->pluck('color')->toArray();
        $bots = $game->game_state['bots'] ?? [];
        $state = $this->initialState($colors, $bots);
        $state['current_color'] = $colors[0];

        $game->update(['status' => 'playing', 'game_state' => $state]);

        return ['success' => true];
    }

    public function rollDice(Game $game, string $color): array
    {
        $state = $game->game_state;

        if ($state['current_color'] !== $color) {
            return ['success' => false, 'message' => '還沒輪到你'];
        }
        if ($state['dice_rolled']) {
            return ['success' => false, 'message' => '已經擲過骰子了'];
        }

        $dice = random_int(1, 6);
        $state['dice_value'] = $dice;
        $state['dice_rolled'] = true;

        if ($dice === 6) {
            $state['consecutive_sixes'] = ($state['consecutive_sixes'] ?? 0) + 1;
        } else {
            $state['consecutive_sixes'] = 0;
        }

        // Three 6s in a row → lose turn
        if ($state['consecutive_sixes'] >= 3) {
            $state['consecutive_sixes'] = 0;
            $state['dice_rolled'] = false;
            $state['dice_value'] = null;
            $state = $this->nextTurn($state);
            $game->update(['game_state' => $state]);

            return ['success' => true, 'dice' => 6, 'three_sixes' => true, 'state' => $state];
        }

        // Check if any valid move exists
        $moves = $this->getValidMoves($state, $color, $dice);
        if (empty($moves)) {
            // No moves available → advance turn
            if ($dice !== 6) {
                $state = $this->nextTurn($state);
            } else {
                $state['dice_rolled'] = false;
                $state['dice_value'] = null;
            }
        }

        $game->update(['game_state' => $state]);

        return ['success' => true, 'dice' => $dice, 'valid_moves' => $moves, 'state' => $state];
    }

    public function movePiece(Game $game, string $color, int $pieceIndex): array
    {
        $state = $game->game_state;

        if ($state['current_color'] !== $color) {
            return ['success' => false, 'message' => '還沒輪到你'];
        }
        if (! $state['dice_rolled']) {
            return ['success' => false, 'message' => '請先擲骰子'];
        }

        $dice = $state['dice_value'];
        $pieces = $state['pieces'][$color];
        $pos = $pieces[$pieceIndex];

        // Validate move
        $validMoves = $this->getValidMoves($state, $color, $dice);
        if (! in_array($pieceIndex, $validMoves)) {
            return ['success' => false, 'message' => '無效的移動'];
        }

        // Enter board from home
        if ($pos === 0 && $dice === 6) {
            $pieces[$pieceIndex] = 1;
        } else {
            $newPos = $pos + $dice;
            if ($newPos > self::MAX_PROGRESS) {
                // Bounce back
                $newPos = self::MAX_PROGRESS - ($newPos - self::MAX_PROGRESS);
            }
            $pieces[$pieceIndex] = $newPos;
        }

        $newPos = $pieces[$pieceIndex];
        $state['pieces'][$color] = $pieces;

        // Capture: check if opponent piece is on same absolute square
        if ($newPos >= 1 && $newPos <= 52) {
            $absPos = $this->relToAbs($color, $newPos);
            if (! in_array($absPos, self::SAFE_SQUARES)) {
                foreach (self::COLORS as $otherColor) {
                    if ($otherColor === $color) {
                        continue;
                    }
                    if (! isset($state['pieces'][$otherColor])) {
                        continue;
                    }
                    foreach ($state['pieces'][$otherColor] as $idx => $otherPos) {
                        if ($otherPos >= 1 && $otherPos <= 52) {
                            if ($this->relToAbs($otherColor, $otherPos) === $absPos) {
                                $state['pieces'][$otherColor][$idx] = 0;
                            }
                        }
                    }
                }
            }
        }

        // Check win
        if ($this->hasWon($state['pieces'][$color])) {
            $state['winner'] = $color;
            $game->update(['status' => 'finished', 'game_state' => $state]);

            return ['success' => true, 'winner' => $color, 'state' => $state];
        }

        // If dice was 6, player rolls again; otherwise next turn
        $state['dice_rolled'] = false;
        $state['dice_value'] = null;
        if ($dice !== 6) {
            $state = $this->nextTurn($state);
        }

        $game->update(['game_state' => $state]);

        return ['success' => true, 'state' => $state];
    }

    public function getValidMoves(array $state, string $color, int $dice): array
    {
        $pieces = $state['pieces'][$color] ?? [];
        $valid = [];

        foreach ($pieces as $i => $pos) {
            if ($pos === self::MAX_PROGRESS) {
                continue;
            } // already finished

            if ($pos === 0) {
                // In home: need 6 to exit
                if ($dice === 6) {
                    $valid[] = $i;
                }

                continue;
            }

            $newPos = $pos + $dice;
            if ($newPos > self::MAX_PROGRESS) {
                // Bounce back: same rule as movePiece
                $bounced = self::MAX_PROGRESS - ($newPos - self::MAX_PROGRESS);
                if ($bounced === $pos) {
                    continue; // Would land on same square — meaningless move
                }
                $valid[] = $i;

                continue;
            }

            $valid[] = $i;
        }

        return $valid;
    }

    /**
     * Convert relative progress (1-52) to absolute track index (0-51)
     */
    public function relToAbs(string $color, int $relPos): int
    {
        $offset = self::START_OFFSETS[$color];

        return ($offset + $relPos - 1) % 52;
    }

    /**
     * Get [row, col] grid coordinates for a piece at given relative progress
     */
    public function getCoords(string $color, int $progress): array
    {
        if ($progress === 0) {
            return [-1, -1]; // in home base (handled separately)
        }
        if ($progress === self::MAX_PROGRESS) {
            return self::CENTER;
        }
        if ($progress >= self::SAFE_LANE_START) {
            $laneIndex = $progress - self::SAFE_LANE_START; // 0-4

            return self::SAFE_LANES[$color][$laneIndex];
        }
        // Main track
        $absIdx = $this->relToAbs($color, $progress);

        return self::BOARD_TRACK[$absIdx];
    }

    // -------------------------------------------------------
    // Bot AI
    // -------------------------------------------------------

    /**
     * Execute a full bot turn (roll + pick best move).
     * Returns the final state after the turn.
     */
    public function executeBotTurn(Game $game): array
    {
        $state = $game->game_state;
        $color = $state['current_color'];
        $bots = $state['bots'] ?? [];

        if (! in_array($color, $bots)) {
            return ['success' => false, 'not_bot' => true];
        }

        // Roll dice
        $dice = random_int(1, 6);
        $state['dice_value'] = $dice;
        $state['dice_rolled'] = true;

        if ($dice === 6) {
            $state['consecutive_sixes'] = ($state['consecutive_sixes'] ?? 0) + 1;
        } else {
            $state['consecutive_sixes'] = 0;
        }

        // Three 6s → lose turn
        if ($state['consecutive_sixes'] >= 3) {
            $state['consecutive_sixes'] = 0;
            $state = $this->nextTurn($state);
            $game->update(['game_state' => $state]);

            return ['success' => true, 'bot_dice' => 6, 'bot_action' => 'three_sixes', 'bot_color' => $color, 'state' => $state];
        }

        $moves = $this->getValidMoves($state, $color, $dice);

        if (empty($moves)) {
            if ($dice !== 6) {
                $state = $this->nextTurn($state);
            } else {
                $state['dice_rolled'] = false;
                $state['dice_value'] = null;
            }
            $game->update(['game_state' => $state]);

            return ['success' => true, 'bot_dice' => $dice, 'bot_action' => 'no_moves', 'bot_color' => $color, 'state' => $state];
        }

        $bestPiece = $this->chooseBotMove($state, $color, $dice, $moves);

        // Temporarily save dice state to DB before movePiece reads it
        $game->update(['game_state' => $state]);

        $result = $this->movePiece($game, $color, $bestPiece);
        $result['bot_dice'] = $dice;
        $result['bot_piece'] = $bestPiece;
        $result['bot_color'] = $color;
        $result['bot_action'] = 'move';

        return $result;
    }

    /**
     * Simple greedy AI: capture > enter safe lane > move farthest piece > exit home
     */
    private function chooseBotMove(array $state, string $color, int $dice, array $moves): int
    {
        // Priority 1: capture an opponent piece
        foreach ($moves as $idx) {
            $pos = $state['pieces'][$color][$idx];
            if ($pos === 0) {
                continue;
            }
            $newPos = $pos + $dice;
            if ($newPos >= 1 && $newPos <= 52) {
                $absNew = $this->relToAbs($color, $newPos);
                if (! in_array($absNew, self::SAFE_SQUARES)) {
                    foreach (self::COLORS as $other) {
                        if ($other === $color || ! isset($state['pieces'][$other])) {
                            continue;
                        }
                        foreach ($state['pieces'][$other] as $otherPos) {
                            if ($otherPos >= 1 && $otherPos <= 52 && $this->relToAbs($other, $otherPos) === $absNew) {
                                return $idx;
                            }
                        }
                    }
                }
            }
        }

        // Priority 2: move into / further along safe lane
        foreach ($moves as $idx) {
            $pos = $state['pieces'][$color][$idx];
            $newPos = ($pos === 0 && $dice === 6) ? 1 : $pos + $dice;
            if ($newPos >= self::SAFE_LANE_START) {
                return $idx;
            }
        }

        // Priority 3: move farthest piece (highest progress)
        $best = $moves[0];
        $bestProgress = $state['pieces'][$color][$moves[0]];
        foreach ($moves as $idx) {
            if ($state['pieces'][$color][$idx] > $bestProgress) {
                $bestProgress = $state['pieces'][$color][$idx];
                $best = $idx;
            }
        }

        return $best;
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function nextTurn(array $state): array
    {
        $activePlayers = array_keys($state['pieces']);
        $idx = array_search($state['current_color'], $activePlayers);
        $next = $activePlayers[($idx + 1) % count($activePlayers)];
        $state['current_color'] = $next;
        $state['dice_rolled'] = false;
        $state['dice_value'] = null;

        return $state;
    }

    private function hasWon(array $pieces): bool
    {
        foreach ($pieces as $pos) {
            if ($pos !== self::MAX_PROGRESS) {
                return false;
            }
        }

        return true;
    }

    private function initialState(array $activeColors = ['yellow'], array $bots = []): array
    {
        $pieces = [];
        foreach ($activeColors as $color) {
            $pieces[$color] = [0, 0, 0, 0];
        }

        return [
            'current_color' => $activeColors[0] ?? 'yellow',
            'dice_value' => null,
            'dice_rolled' => false,
            'consecutive_sixes' => 0,
            'winner' => null,
            'pieces' => $pieces,
            'bots' => $bots,
            'turn_count' => 0,
        ];
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Game::where('code', $code)->exists());

        return $code;
    }
}
