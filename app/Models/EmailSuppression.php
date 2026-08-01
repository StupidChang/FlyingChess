<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSuppression extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['email', 'reason', 'detail'];

    /** 地址一律轉小寫再比對:SES 回報的大小寫不保證跟使用者輸入的一致。 */
    public static function isSuppressed(string $email): bool
    {
        return static::where('email', mb_strtolower(trim($email)))->exists();
    }

    public static function suppress(string $email, string $reason, ?string $detail = null): void
    {
        static::updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['reason' => $reason, 'detail' => $detail],
        );
    }
}
