<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapsuleAnswer extends Model
{
    protected $fillable = ['question_id', 'role', 'answer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(CapsuleQuestion::class, 'question_id');
    }
}
