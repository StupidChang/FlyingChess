<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    /** feedback 是不可數名詞,Eloquent 猜出來的複數不可靠,直接寫死 */
    protected $table = 'feedback';

    protected $fillable = [
        'type', 'message', 'contact', 'page_path', 'locale', 'user_id', 'user_agent', 'status',
    ];

    public const TYPE_BUG = 'bug';

    public const TYPE_PROMPT = 'prompt';

    public const TYPE_FEATURE = 'feature';

    public const TYPE_OTHER = 'other';

    public const TYPES = [self::TYPE_BUG, self::TYPE_PROMPT, self::TYPE_FEATURE, self::TYPE_OTHER];

    public const STATUS_NEW = 'new';

    public const STATUS_DOING = 'doing';

    public const STATUS_DONE = 'done';

    public const STATUS_SPAM = 'spam';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_DOING, self::STATUS_DONE, self::STATUS_SPAM];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 使用者送來的 page_path 只有「站內相對路徑」是可信的。
     *
     * 這個值來自網址上的 ?from=,等於是使用者可控字串。存進資料庫之前先收乾:
     * 必須以單一 / 開頭(擋掉 //evil.com 這種被當成協定相對網址的寫法),不能
     * 含有 : 或空白。不合格就當作沒填 —— 這個欄位只是查問題的線索,不值得為它
     * 冒任何風險。
     */
    public static function sanitizePagePath(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '' || ! str_starts_with($raw, '/') || str_starts_with($raw, '//')) {
            return null;
        }

        if (preg_match('/[\s:\\\\]/', $raw)) {
            return null;
        }

        return mb_substr($raw, 0, 200);
    }
}
