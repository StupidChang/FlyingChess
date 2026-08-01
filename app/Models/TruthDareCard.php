<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TruthDareCard extends Model
{
    use HasTranslations;

    protected $fillable = ['category', 'audience', 'content', 'content_translations', 'tier', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
    ];

    /** 題目類型。後台下拉與驗證共用。 */
    public const CATEGORIES = ['truth' => '真心話', 'dare' => '大冒險'];

    /** 適用人數。跟類型是兩個獨立的軸,壓成一個就會抽到對不上場合的題目。 */
    public const AUDIENCES = ['both' => '通用', 'couple' => '情侶', 'party' => '多人'];

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

    public function isFree(): bool
    {
        return $this->tier === 'free';
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium';
    }
}
