<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class WheelSegment extends Model
{
    use HasTranslations;

    protected $fillable = ['is_canary', 'content', 'content_translations', 'tier', 'is_paid', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
        'is_paid' => 'boolean',
        'is_canary' => 'boolean',
    ];

    /** 強度。名稱不帶「(付費)」—— 收費是每一題自己的 is_paid。 */
    public const TIERS = [
        'mild' => '輕鬆',
        'mild_plus' => '微撩',
        'medium' => '親密',
        'medium_plus' => '挑逗',
        'intense' => '大膽',
    ];

    /** 由輕到重的順序。 */
    public const TIER_ORDER = ['mild', 'mild_plus', 'medium', 'medium_plus', 'intense'];

    /** 新增時的預設。原本整級收費的那一級預設打勾。 */
    public static function defaultIsPaid(?string $tier): bool
    {
        return $tier === 'intense';
    }

    public array $translatable = ['content_translations'];

    /**
     * Master locale reads legacy column directly; non-master uses translations
     * JSON with fallback to legacy. See LocaleHelper::pickTranslation().
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
}
