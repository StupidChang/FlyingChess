<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    /** 只有 created_at,沒有 updated_at —— 一筆瀏覽紀錄寫下去就不會再改。 */
    public const UPDATED_AT = null;

    protected $fillable = [
        'path', 'locale', 'user_id', 'visitor_hash', 'referer_host',
    ];
}
