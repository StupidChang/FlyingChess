<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TruthDareCard extends Model
{
    use HasTranslations;

    protected $fillable = ['category', 'audience', 'level', 'is_paid', 'content', 'content_translations', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

    /** 題目類型。後台下拉與驗證共用。 */
    public const CATEGORIES = ['truth' => '真心話', 'dare' => '大冒險'];

    /** 適用人數。跟類型是兩個獨立的軸,壓成一個就會抽到對不上場合的題目。 */
    public const AUDIENCES = ['both' => '通用', 'couple' => '情侶', 'party' => '多人'];

    /**
     * 尺度,由輕到重。
     *
     * 整個站都是成人向,所以不分「一般／18禁」—— 免費那批本來就寫著「曖昧級」,
     * 不是普遍級。用跟其他四個小遊戲題庫同一套詞彙,後台與玩家只要記一套。
     */
    /* 標籤不帶「(付費)」—— 收費是每張卡片自己的 is_paid,不是這一級的屬性。
       重度也可以有免費的題目,中度也可以有付費的。 */
    public const LEVELS = [
        'mild' => '輕度',
        'mild_plus' => '輕中',
        'medium' => '中度',
        'medium_plus' => '中重',
        'intense' => '重度',
    ];

    /** 由輕到重的順序。升溫的階梯與後台排序都靠它。 */
    public const LEVEL_ORDER = ['mild', 'mild_plus', 'medium', 'medium_plus', 'intense'];

    /**
     * 新增卡片時預設要不要收費。
     *
     * 只是後台表單與匯入的預設值 —— 真正的界線是每張卡片自己的 is_paid,
     * 尺度與收費是兩件事:中度裡也會有想留給付費的題目。
     */
    public const DEFAULT_PAID_LEVELS = ['intense'];

    public static function defaultIsPaid(?string $level): bool
    {
        return in_array($level, self::DEFAULT_PAID_LEVELS, true);
    }

    /**
     * 某個場合抽得到哪些 audience。
     *
     * 「通用」兩邊都出得來,所以情侶場永遠是 both + couple,多人場是 both + party。
     */
    public static function audiencesFor(string $mode): array
    {
        return $mode === 'party' ? ['both', 'party'] : ['both', 'couple'];
    }

    public array $translatable = ['content_translations'];

    /**
     * Read-side accessor: $card->content returns the localized string for the
     * current app locale. The master locale (zh_TW) always reads the legacy
     * `content` column directly so admin edits to that column take effect
     * immediately without needing to also update the JSON. Non-master locales
     * read translations[$locale], falling back to the master column.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => LocaleHelper::pickTranslation(
                $this->getRawOriginal('content_translations'),
                $value,
            ),
            set: fn ($value) => $value,
        );
    }

    /** 沒有權限的人抽得到的那些。 */
    public function scopeFreeToPlay($query)
    {
        return $query->where('is_paid', false);
    }
}
