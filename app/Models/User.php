<?php

namespace App\Models;

use App\Support\LocaleHelper;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'locale',
        'password',
        'premium_expires_at',
        'is_admin',
        'is_banned',
        'banned_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'premium_expires_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    /**
     * Laravel wraps notification sending in Lang::withLocale() using this value,
     * which also fixes the {locale} prefix on verification / reset links built
     * in AppServiceProvider. Older rows have no locale, so fall back to the site
     * default rather than whatever locale the sending process happens to be in.
     */
    public function preferredLocale(): string
    {
        return LocaleHelper::isSupported((string) $this->locale)
            ? $this->locale
            : LocaleHelper::defaultLocale();
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function dice(): HasMany
    {
        return $this->hasMany(Dice::class);
    }

    public function isPremium(): bool
    {
        return $this->premium_expires_at && $this->premium_expires_at->isFuture();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isBanned(): bool
    {
        return (bool) $this->is_banned;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }
}
