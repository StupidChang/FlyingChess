<?php

namespace App\Models;

use App\Support\ContentTranslations;
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
        'reference_image', 'recommended_players',
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
        'recommended_players' => 'integer',
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
                // 預設轉盤的六格文字跟範本一樣是內建內容,對得到字典就翻
                'text' => (string) ContentTranslations::translate((string) ($slots[$i]['text'] ?? '')),
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

    /**
     * 說明沒有 *_translations 欄位,只查內建內容字典(範本棋盤的說明在那裡)。
     * 使用者自己寫的說明對不到字典,原樣顯示。
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ContentTranslations::translate($value),
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

    /**
     * 多人棋盤:題目寫的是「在場的異性／左邊的人」,不是「對方」。
     * 兩人玩會卡在「找兩位異性」這種格子,所以列表上要標出來。
     */
    public function isGroupPlay(): bool
    {
        return ($this->recommended_players ?? 2) > 2;
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
     * 這張棋盤的內容在哪些語系有完整翻譯(名稱、說明、每一格、進場轉盤)。
     *
     * 內容來自 ContentTranslations 字典:範本棋盤翻好了就全對得上,使用者改過的
     * 格子對不上。沒翻完的語系,頁面會退回繁中顯示 —— 那一頁就不該被收錄,也不該
     * 出現在那個語系的 sitemap 或 hreflang 裡(同 config/traits.php 的 translated)。
     *
     * @return array<int, string> 語系代碼
     */
    public function translatedLocales(): array
    {
        $wheel = is_array($this->start_wheel) && ! empty($this->start_wheel['enabled'])
            ? array_column((array) ($this->start_wheel['segments'] ?? []), 'text')
            : [];

        $texts = [
            $this->getRawOriginal('name'),
            $this->getRawOriginal('description'),
            ...$this->squares->map(fn ($sq) => $sq->getRawOriginal('text'))->all(),
            ...$wheel,
        ];

        return array_values(array_filter(
            array_keys(LocaleHelper::supported()),
            fn ($locale) => ContentTranslations::covers($texts, $locale),
        ));
    }

    public function isTranslatedFor(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), $this->translatedLocales(), true);
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

    /** 一眼看得出調性、但看不完 —— 付費範本預覽開放的格數。 */
    public const PREVIEW_OPEN_SQUARES = 8;

    /**
     * 付費範本預覽要開放哪幾格(範本預覽頁與大廳的快速預覽共用)。散在整張棋盤上
     * 而不是集中在開頭 —— 前八格通常都是暖身,只看那幾格會以為整張棋盤都很溫和,
     * 反而讓人覺得不值得解鎖。
     *
     * ⚠ 這個選擇必須**對同一張棋盤永遠一樣**。用真的亂數的話,重新整理幾次
     * 就能把整張棋盤看完,等於沒鎖。所以用 (棋盤 id + 格號 + APP_KEY) 的雜湊
     * 排序來挑:看起來是隨機的,但同一張棋盤每次都得到同一批,而且加了
     * APP_KEY 之後外人也算不出下一次會開哪幾格。兩個入口共用這一份,
     * 不然各開各的八格,合起來就多看了一倍。
     */
    public function previewOpenPositions(): array
    {
        return $this->squares
            ->pluck('position')
            ->sortBy(fn ($p) => crc32($this->id.':'.$p.':'.config('app.key')))
            ->take(self::PREVIEW_OPEN_SQUARES)
            ->sort()
            ->values()
            ->all();
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
