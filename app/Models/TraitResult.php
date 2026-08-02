<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraitResult extends Model
{
    protected $fillable = ['user_id', 'top_trait', 'traits', 'axes'];

    protected $casts = [
        'traits' => 'array',
        'axes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 除了主屬性之外,還有哪幾種偏高。 */
    public function runnersUp(int $limit = 3, int $threshold = 50): array
    {
        return array_slice(
            array_filter(
                array_slice($this->traits ?? [], 1),
                fn ($t) => ($t['pct'] ?? 0) >= $threshold
            ),
            0,
            $limit
        );
    }
}
