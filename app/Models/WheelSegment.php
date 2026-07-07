<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class WheelSegment extends Model
{
    use HasTranslations;

    protected $fillable = ['content', 'content_translations', 'tier', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
    ];

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
