<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapsuleQuestion extends Model
{
    protected $fillable = ['capsule_id', 'question', 'position'];

    public function capsule(): BelongsTo
    {
        return $this->belongsTo(TimeCapsule::class, 'capsule_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CapsuleAnswer::class, 'question_id');
    }
}
