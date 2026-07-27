<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomWheel extends Model
{
    protected $fillable = ['user_id', 'name', 'items'];

    /** 每位使用者最多儲存幾組(避免單一帳號無限寫入) */
    public const MAX_PER_USER = 20;

    /** 單一轉盤最多幾個選項 —— 與前端編輯器的上限一致 */
    public const MAX_ITEMS = 20;

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
