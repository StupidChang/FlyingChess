<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BucketItem extends Model
{
    protected $fillable = [
        'bucket_list_id', 'content', 'proposer', 'owner_vote', 'partner_vote',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(BucketList::class, 'bucket_list_id');
    }

    public function status(): string
    {
        $o = $this->owner_vote;
        $p = $this->partner_vote;

        if (!$o || !$p) return 'pending';
        if ($o === 'yes' && $p === 'yes') return 'agreed';
        if ($o === 'no' || $p === 'no') return 'rejected';
        return 'maybe';
    }
}
