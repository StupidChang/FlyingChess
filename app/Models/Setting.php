<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 通用鍵值設定。value 一律以 JSON 存取,所以能放陣列(例如整份定價覆寫)。
 *
 * 讀取有快取:設定很少改、每個請求都會讀(定價在 layout / 付費頁都用得到),
 * 不快取的話等於每次請求都打一次 DB。改動時清掉對應的快取鍵。
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'setting:';

    private const CACHE_TTL = 3600;

    /** 讀一個設定(JSON 解碼後)。沒有就回 $default。 */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $raw = Cache::remember(self::CACHE_PREFIX.$key, self::CACHE_TTL, function () use ($key) {
            // 用哨兵值區分「沒這筆」與「這筆存的就是 null」,兩者都要能被快取。
            $row = static::find($key);

            return $row ? $row->value : '__MISSING__';
        });

        if ($raw === '__MISSING__') {
            return $default;
        }

        $decoded = json_decode((string) $raw, true);

        return $decoded === null && json_last_error() !== JSON_ERROR_NONE ? $default : $decoded;
    }

    /** 寫一個設定(JSON 編碼)。 */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        );

        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
