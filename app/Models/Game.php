<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    protected $fillable = ['code', 'game_type', 'status', 'max_players', 'is_private', 'game_state', 'finished_at'];

    protected $casts = [
        'game_state' => 'array',
        'max_players' => 'integer',
        'is_private' => 'boolean',

        'finished_at' => 'datetime',
    ];

    /**
     * 開場的來源。
     *
     * 掛在模型上而不是寫在兩個 Service 裡 —— 這樣以後多一種遊戲也不會漏掉,
     * 而且 Service 不用為了記錄來源去碰 request。
     *
     * 與 page_views 一致:只留 referer 的主機名與語系,不存完整網址、IP 或 UA。
     * 站內互連不算來源,記了只會把真正的外部來源洗掉。
     */
    protected static function booted(): void
    {
        static::creating(function (self $game) {
            /* 語系一律記。從命令列建立的場次會拿到預設語系 —— 不特別排除,
               因為 runningInConsole() 在 phpunit 底下也是 true,那條判斷擋掉的
               會是測試而不是 artisan。 */
            $game->origin_locale ??= app()->getLocale();

            if ($game->origin_referer === null && ($referer = request()->headers->get('referer'))) {
                $host = parse_url($referer, PHP_URL_HOST);
                if ($host && $host !== request()->getHost()) {
                    $game->origin_referer = mb_substr($host, 0, 191);
                }
            }
        });
    }

    public function players(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    /**
     * 開這一場的人。
     *
     * 用 hasOne 而不是在呼叫端 ->players->firstWhere('is_host') —— 後台列表要能
     * 一次 eager load,不然一頁 100 場就是 100 次查詢。
     *
     * 舊資料有可能一場裡沒有任何 is_host(中途離開會刪掉玩家列),所以呼叫端
     * 一律要當它可能是 null。
     */
    public function host(): HasOne
    {
        return $this->hasOne(GamePlayer::class)->where('is_host', true);
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
