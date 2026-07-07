<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class BoardSquare extends Model
{
    use HasTranslations;

    protected $fillable = ['board_id', 'position', 'text', 'text_translations', 'color', 'fly_to', 'grid_row', 'grid_col', 'machine_translated_at'];

    protected $casts = [
        'machine_translated_at' => 'datetime',
    ];

    public array $translatable = ['text_translations'];

    /**
     * Master locale (zh_TW) reads $value directly so admin edits propagate
     * without JSON sync. Non-master locales read translations[$locale] with
     * fallback to $value. See LocaleHelper::pickTranslation() for rationale.
     */
    protected function text(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => LocaleHelper::pickTranslation(
                $this->getRawOriginal('text_translations'),
                $value,
            ),
            set: fn ($value) => $value,
        );
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
