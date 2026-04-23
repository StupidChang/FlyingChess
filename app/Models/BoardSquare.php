<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardSquare extends Model
{
    protected $fillable = ['board_id', 'position', 'text', 'color', 'fly_to', 'grid_row', 'grid_col'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
