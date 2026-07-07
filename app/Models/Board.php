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
        'user_id', 'share_code', 'machine_translated_at',
        'publish_status', 'published_at', 'publish_note',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_template' => 'boolean',
        'is_premium_template' => 'boolean',
        'path_data' => 'array',
        'canvas_rows' => 'integer',
        'canvas_cols' => 'integer',
        'machine_translated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public array $translatable = ['name_translations'];

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

    public function squaresArray(): array
    {
        return $this->squares->keyBy('position')->map(fn ($s) => [
            'text' => $s->text,
            'color' => $s->color,
            'fly_to' => $s->fly_to,
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
