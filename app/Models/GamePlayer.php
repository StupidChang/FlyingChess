<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayer extends Model
{
    protected $fillable = ['game_id', 'session_id', 'player_name', 'color', 'is_host', 'user_id'];

    protected $casts = [
        'is_host' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 沒有登入的玩家在後台的代號。
     *
     * 用途是「同一個訪客開的兩場能不能看出是同一個人」,所以要穩定;但**不能**
     * 直接把 session_id 印在後台 —— 那是還在生效的 session 識別碼,看得到就代表
     * 能冒用。所以雜湊過再截短,並且用 APP_KEY 加鹽,拿到代號也回推不出原值。
     *
     * 同機遊玩的其他人共用房主的 session_id 加後綴,所以他們會各自拿到不同代號,
     * 這是對的:那本來就是不同的玩家列。
     */
    public function guestCode(): string
    {
        return strtoupper(substr(
            hash_hmac('sha256', (string) $this->session_id, (string) config('app.key')),
            0, 6
        ));
    }
}
