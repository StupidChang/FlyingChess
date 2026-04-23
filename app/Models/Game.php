<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = ['code', 'game_type', 'status', 'max_players', 'is_private', 'game_state'];

    protected $casts = [
        'game_state' => 'array',
        'max_players' => 'integer',
        'is_private' => 'boolean',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isFull(): bool
    {
        return $this->players()->count() >= $this->max_players;
    }
}
