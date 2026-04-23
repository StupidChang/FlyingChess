<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Board extends Model
{
    protected $fillable = [
        'name', 'description', 'is_default',
        'is_template', 'is_premium_template',
        'canvas_rows', 'canvas_cols', 'path_data',
        'user_id', 'share_code',
    ];

    protected $casts = [
        'is_default'           => 'boolean',
        'is_template'          => 'boolean',
        'is_premium_template'  => 'boolean',
        'path_data'            => 'array',
        'canvas_rows'          => 'integer',
        'canvas_cols'          => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Board $board) {
            if (empty($board->share_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (static::where('share_code', $code)->exists());
                $board->share_code = $code;
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

    public function squaresArray(): array
    {
        return $this->squares->keyBy('position')->map(fn($s) => [
            'text'     => $s->text,
            'color'    => $s->color,
            'fly_to'   => $s->fly_to,
            'grid_row' => $s->grid_row,
            'grid_col' => $s->grid_col,
        ])->toArray();
    }

    /** Resolve the effective path for a given gender. */
    public function resolvedPath(string $gender = 'all'): array
    {
        $pd = $this->path_data ?? [];
        if ($gender !== 'all' && !empty($pd[$gender])) {
            return $pd[$gender];
        }
        if (!empty($pd['all'])) {
            return $pd['all'];
        }
        // Fallback: all positions sorted
        return $this->squares->pluck('position')->sort()->values()->toArray();
    }
}
