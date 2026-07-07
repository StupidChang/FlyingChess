<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TimeCapsule extends Model
{
    protected $fillable = [
        'share_code', 'title', 'open_at', 'sealed_at', 'opened_at',
        'notify_email', 'owner_token', 'partner_token', 'reminder_sent',
    ];

    protected $casts = [
        'open_at'        => 'date',
        'sealed_at'      => 'datetime',
        'opened_at'      => 'datetime',
        'reminder_sent'  => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TimeCapsule $capsule) {
            if (empty($capsule->share_code)) {
                $maxAttempts = 10;
                for ($i = 0; $i < $maxAttempts; $i++) {
                    $code = strtoupper(Str::random(8));
                    if (!static::where('share_code', $code)->exists()) {
                        $capsule->share_code = $code;
                        return;
                    }
                }
                Log::alert('time_capsule share_code generation exhausted retries', [
                    'attempts' => $maxAttempts,
                ]);
                throw new RuntimeException('Unable to generate unique share_code after ' . $maxAttempts . ' attempts');
            }
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CapsuleQuestion::class, 'capsule_id')->orderBy('position');
    }

    public function isSealed(): bool
    {
        return !is_null($this->sealed_at);
    }

    public function isOpenable(): bool
    {
        return $this->isSealed() && Carbon::today()->greaterThanOrEqualTo($this->open_at);
    }

    public function daysUntilOpen(): int
    {
        return Carbon::today()->diffInDays($this->open_at, false);
    }
}
