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
     * 「在哪一頁遇到的」—— 存成本站的完整網址(含 ?query),後台點了就能打開同一頁。
     *
     * 這個值來自網址上的 ?from=,等於是使用者可控字串。存進資料庫之前先收乾:
     *
     * - 完整網址:scheme 只能是 http(s),網域必須是本站(APP_URL 的網域或它的 www 版)
     * - 站內路徑:以單一 / 開頭(擋掉 //evil.com 這種協定相對網址),補上 APP_URL 變成完整網址
     * - 不能含空白或反斜線
     *
     * 其他一律當作沒填 —— 這個欄位只是查問題的線索,不值得為它冒任何風險。
     */
    public static function sanitizePageUrl(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '' || preg_match('/[\s\\\\]/', $raw)) {
            return null;
        }

        $base = rtrim((string) config('app.url'), '/');

        if (str_starts_with($raw, '/')) {
            if (str_starts_with($raw, '//') || str_contains($raw, ':')) {
                return null;
            }

            return mb_substr($base.$raw, 0, 500);
        }

        $parts = parse_url($raw);
        $ours = strtolower((string) parse_url($base, PHP_URL_HOST));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || $host === '' || isset($parts['user']) || isset($parts['pass'])
            || ($host !== $ours && $host !== 'www.'.$ours && 'www.'.$host !== $ours)) {
            return null;
        }

        return mb_substr($raw, 0, 500);
    }
}
