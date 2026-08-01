<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TruthDareCard extends Model
{
    use HasTranslations;

    protected $fillable = ['category', 'audience', 'level', 'content', 'content_translations', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
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
    public const LEVELS = ['mild' => '輕度', 'medium' => '中度', 'intense' => '重度(付費)'];

    /** 由輕到重的順序。升溫的階梯與後台排序都靠它。 */
    public const LEVEL_ORDER = ['mild', 'medium', 'intense'];

    /** 要付費或看廣告才抽得到的等級。付費界線只寫在這裡。 */
    public const PAID_LEVELS = ['intense'];

    /**
     * 現在抽得到哪幾級。
     *
     * @param  bool  $hasPaidAccess  房主是會員,或這台裝置在看廣告解鎖的時限內
     */
    public static function levelsFor(bool $hasPaidAccess): array
    {
        return $hasPaidAccess
            ? self::LEVEL_ORDER
            : array_values(array_diff(self::LEVEL_ORDER, self::PAID_LEVELS));
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

    public function isPaid(): bool
    {
        return in_array($this->level, self::PAID_LEVELS, true);
    }
}
