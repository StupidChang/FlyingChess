<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TruthDareCard extends Model
{
    use HasTranslations;

    protected $fillable = ['category', 'content', 'content_translations', 'tier', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
    ];

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
