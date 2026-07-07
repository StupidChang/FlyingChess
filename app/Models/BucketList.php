<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class BucketList extends Model
{
    protected $fillable = [
        'share_code', 'title', 'owner_token', 'partner_token',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BucketList $list) {
            if (empty($list->share_code)) {
                $maxAttempts = 10;
                for ($i = 0; $i < $maxAttempts; $i++) {
                    $code = strtoupper(Str::random(8));
                    if (!static::where('share_code', $code)->exists()) {
                        $list->share_code = $code;
                        return;
                    }
                }
                Log::alert('bucket_list share_code generation exhausted retries', [
                    'attempts' => $maxAttempts,
                ]);
                throw new RuntimeException('Unable to generate unique share_code after ' . $maxAttempts . ' attempts');
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(BucketItem::class)->orderBy('id');
    }
}
