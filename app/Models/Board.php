<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Translatable\HasTranslations;

class Board extends Model
{
    use HasTranslations;

    public const PUBLISH_PENDING = 'pending';

    public const PUBLISH_APPROVED = 'approved';

    public const PUBLISH_REJECTED = 'rejected';

    protected $fillable = [
        'name', 'name_translations', 'description', 'is_default',
        'is_template', 'is_premium_template',
        'canvas_rows', 'canvas_cols', 'path_data',
        'start_wheel', 'capture_enabled',
        'user_id', 'share_code', 'machine_translated_at',
        'publish_status', 'published_at', 'publish_note',
        'reference_image',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_template' => 'boolean',
        'is_premium_template' => 'boolean',
        'path_data' => 'array',
        'start_wheel' => 'array',
        'capture_enabled' => 'boolean',
        'canvas_rows' => 'integer',
        'canvas_cols' => 'integer',
        'machine_translated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public array $translatable = ['name_translations'];

    /**
     * The entry wheel from the physical V8.0 board — six slots read by dice face.
     * `enter` puts the piece on the track, `reroll` gives the turn straight back.
     * Used as the starting point when a board first switches the wheel on.
     */
    public const DEFAULT_START_WHEEL = [
        ['text' => '喝一口', 'enter' => false, 'reroll' => false],
        ['text' => '再擲一次', 'enter' => false, 'reroll' => true],
        ['text' => '親吻對方伴侶', 'enter' => false, 'reroll' => false],
        ['text' => '喝一杯', 'enter' => false, 'reroll' => false],
        ['text' => '再擲一次', 'enter' => false, 'reroll' => true],
        ['text' => '進入棋盤', 'enter' => true, 'reroll' => false],
    ];

    /** Normalised wheel for the front end: null when the board has none. */
    public function startWheel(): ?array
    {
        $wheel = $this->start_wheel;

        if (! is_array($wheel) || empty($wheel['enabled'])) {
            return null;
        }

        $slots = array_values((array) ($wheel['segments'] ?? []));

        // A wheel is always read by dice face, so it must have exactly six slots.
        for ($i = 0; $i < 6; $i++) {
            $slots[$i] = [
                'text' => (string) ($slots[$i]['text'] ?? ''),
                'enter' => (bool) ($slots[$i]['enter'] ?? false),
                'reroll' => (bool) ($slots[$i]['reroll'] ?? false),
            ];
        }

        return array_slice($slots, 0, 6);
    }

    /**
     * Master locale reads $value directly; non-master uses translations JSON
     * with fallback to $value. See LocaleHelper::pickTranslation().
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => LocaleHelper::pickTranslation(
                $this->getRawOriginal('name_translations'),
                $value,
            ),
            set: fn ($value) => $value,
        );
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Board $board) {
            if (empty($board->share_code)) {
                $maxAttempts = 10;
                for ($i = 0; $i < $maxAttempts; $i++) {
                    $code = strtoupper(Str::random(8));
                    if (! static::where('share_code', $code)->exists()) {
                        $board->share_code = $code;

                        return;
                    }
                }
                Log::alert('share_code generation exhausted retries', [
                    'attempts' => $maxAttempts,
                    'board_user_id' => $board->user_id,
                ]);
                throw new RuntimeException('Unable to generate unique share_code after '.$maxAttempts.' attempts');
            }
        });
    }

    public function squares(): HasMany
    {
        return $this->hasMany(BoardSquare::class)->orderBy('position');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefault(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }

    public function isPublished(): bool
    {
        return $this->publish_status === self::PUBLISH_APPROVED;
    }

    /** Community boards: user-published and admin-approved. */
    public function scopePublished($query)
    {
        return $query->where('publish_status', self::PUBLISH_APPROVED);
    }

    /** Whether this board may be opened via its numeric /play/{board} URL. */
    public function isPubliclyPlayable(): bool
    {
        return $this->is_default || $this->is_template || $this->isPublished();
    }

    /**
     * 這張棋盤的頁面可不可以被索引 —— 也就是它該不該出現在 sitemap 裡。
     *
     * 「可以玩」不等於「可以索引」:付費範本的網址不是私密的,但沒有付費資格的
     * 訪客(包含 Googlebot)拿到的是一個 302 轉去付費頁。sitemap 裡放會轉址的
     * 網址,在 Search Console 就是一筆錯誤,而且白白吃掉新網域本來就很少的
     * 爬取預算。
     *
     * 這是 sitemap 查詢與 play 頁面 robots meta 的**單一真相**。這兩個地方以前
     * 各寫一份自己的規則,而且已經走鐘了:sitemap 收了範本與社群審核過的棋盤,
     * view 卻把「除了預設棋盤以外的全部」標成 noindex —— 收錄清單與頁面自己的
     * 宣告互相矛盾。要改條件就改這裡,兩邊會一起跟上。
     */
    public function isPubliclyIndexable(): bool
    {
        if ($this->is_premium_template) {
            return false;   // 對沒付費的訪客(含 Googlebot)只會回 302
        }

        // 預設棋盤的正本網址是乾淨的 /play,它由 sitemap 的靜態路徑收錄,不需要 share_code
        if ($this->is_default) {
            return true;
        }

        /* 其餘棋盤一定要有 share_code 才可索引。沒有 share_code 的棋盤只能用數字
           網址打開、不在 sitemap 裡,卻又標成 index —— 那正是「可索引集合」和
           「收錄集合」不一致的縫。實際造成的問題:站上有兩張都叫「輕度暖身版」的
           棋盤(預設那張與一個範本),兩頁的 <title> 一模一樣,等於自己跟自己搶排名。
           要讓某個範本進索引,給它一個 share_code(而且名字不要和別人重複)。 */
        return $this->share_code !== null && $this->isPubliclyPlayable();
    }

    /**
     * isPubliclyIndexable() 的查詢版孿生兄弟。改一個就要改另一個。
     *
     * 注意 SitemapController 在這個 scope 之外還會再加一個 whereNotNull('share_code')。
     * 那不是重複,是刻意的:預設棋盤可索引,但它在 sitemap 裡的網址是靜態的 /play,
     * 不是 /play/share/{code} —— 所以要從「棋盤網址」那一段排除掉。
     */
    public function scopePubliclyIndexable($query)
    {
        return $query->where('is_premium_template', false)
            ->where(fn ($q) => $q->where('is_default', true)
                ->orWhere(fn ($inner) => $inner->whereNotNull('share_code')
                    ->where(fn ($kind) => $kind->where('is_template', true)
                        ->orWhere('publish_status', self::PUBLISH_APPROVED))));
    }

    /**
     * 這張棋盤唯一該被收錄的網址。
     *
     * 同一張棋盤有兩到三個網址(/play、/play/{id}、/play/share/{code}),內容完全
     * 一樣。以前每一頁都用 url()->current() 宣告自己是 canonical,等於告訴搜尋
     * 引擎「這幾個是不同的頁面」—— 那是教科書級的重複內容。
     *
     * 選擇的偏好順序刻意和 sitemap 一致:預設棋盤是乾淨的 /play,其餘走
     * share_code。沒有 share_code 的(理論上不該出現在公開頁)退回數字網址。
     */
    public function canonicalPlayUrl(): string
    {
        /* locale 一律明確傳。這些路由都在 {locale} 前綴底下,而那個參數的預設值是
           SetLocale 中介層在請求裡設的 —— 在 web 請求外(CLI、artisan、佇列)沒有人
           設過它,route() 會丟 UrlGenerationException。這是 model 上的方法,比 view
           更可能在那些地方被呼叫,所以不要依賴請求狀態。 */
        $locale = LocaleHelper::localeToPrefix(app()->getLocale())
            ?? LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale());

        if ($this->is_default) {
            return route('play', ['locale' => $locale]);
        }

        return $this->share_code
            ? route('play.code', ['locale' => $locale, 'code' => $this->share_code])
            : route('play.board', ['locale' => $locale, 'board' => $this->id]);
    }

    public function squaresArray(): array
    {
        return $this->squares->keyBy('position')->map(fn ($s) => [
            'text' => $s->text,
            'color' => $s->color,
            'fly_to' => $s->fly_to,
            'move_steps' => $s->move_steps,
            'skip_turn' => (bool) $s->skip_turn,
            'grid_row' => $s->grid_row,
            'grid_col' => $s->grid_col,
        ])->toArray();
    }

    /** Resolve the effective path for a given gender. */
    public function resolvedPath(string $gender = 'all'): array
    {
        $pd = $this->path_data ?? [];
        if ($gender !== 'all' && ! empty($pd[$gender])) {
            return $pd[$gender];
        }
        if (! empty($pd['all'])) {
            return $pd['all'];
        }

        // Fallback: all positions sorted
        return $this->squares->pluck('position')->sort()->values()->toArray();
    }
}
