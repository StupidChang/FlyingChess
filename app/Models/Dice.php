<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dice extends Model
{
    protected $table = 'dice';

    protected $fillable = ['user_id', 'category', 'name', 'faces'];

    protected function casts(): array
    {
        return [
            'faces' => 'array',
        ];
    }

    /** Allowed categories a custom die can belong to. */
    public const CATEGORIES = ['action', 'part', 'time', 'prop'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
