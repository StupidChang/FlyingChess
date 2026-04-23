<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TruthDareCard extends Model
{
    protected $fillable = ['category', 'content', 'tier'];

    public function isFree(): bool
    {
        return $this->tier === 'free';
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium';
    }
}
