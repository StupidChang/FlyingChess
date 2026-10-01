<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 累計計數器。一個 key 一個數字,只會往上加。
 *
 * 目前唯一的 key 是屬性測驗的完成數,用來在結果頁講「你是第 N 位」。這個數字
 * 不能從 trait_results 算 —— 那張表只存登入者(見它的 migration)。
 */
class Counter extends Model
{
    /** 屬性測驗交卷完成數。 */
    public const TRAIT_TEST = 'trait_test_completions';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'count'];

    protected $casts = ['count' => 'integer'];

    /**
     * 加一,回傳**加完之後**的值 —— 也就是「這一位是第幾位」。
     *
     * 整段包在交易裡才拿得到自己那一號:先 increment 再讀,中間如果有別人插進來,
     * 兩個人會拿到同一個號碼。SQLite 的寫入交易會互相排隊,所以這樣就夠。
     */
    public static function bump(string $key): int
    {
        return DB::transaction(function () use ($key) {
            // 第一次呼叫時那一列還不存在;insertOrIgnore 讓兩個人同時第一次也不會撞主鍵
            DB::table('counters')->insertOrIgnore([
                'key' => $key,
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('counters')->where('key', $key)->increment('count', 1, ['updated_at' => now()]);

            return (int) DB::table('counters')->where('key', $key)->value('count');
        });
    }

    /** 目前的累計值(不加)。沒有這個 key 就是 0。 */
    public static function total(string $key): int
    {
        return (int) (DB::table('counters')->where('key', $key)->value('count') ?? 0);
    }
}
