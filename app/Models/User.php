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
use Illuminate\Support\Facades\Storage;

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
        // 個人化 / 聯誼欄位。都是非特權欄位,且一律以「已驗證的資料」寫入。
        'city',
        'looking_for',
        'bio',
        'avatar_path',
        'banner_path',
        'avatar_pos_x',
        'avatar_pos_y',
        'banner_pos_x',
        'banner_pos_y',
        'profile_theme',
        'profile_public',
        'show_traits',
        'show_boards',
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
            'profile_public' => 'boolean',
            'show_traits' => 'boolean',
            'show_boards' => 'boolean',
        ];
    }

    /** 上傳檔案(頭像 / 橫幅)所在的 disk,見 config/profile.upload_disk。 */
    private function uploadDisk(): string
    {
        return (string) config('profile.upload_disk', 'public');
    }

    /**
     * 頭像網址。沒上傳過就回 null,由 view 退回「名字首字」的預設頭像。
     * path 存的是 upload disk 上的相對路徑(本機 public 或 R2),經該 disk 的 url() 對外。
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk($this->uploadDisk())->url($this->avatar_path) : null;
    }

    /** 個人頁橫幅圖網址。沒上傳過就回 null,由 view 退回主題漸層。 */
    public function bannerUrl(): ?string
    {
        return $this->banner_path ? Storage::disk($this->uploadDisk())->url($this->banner_path) : null;
    }

    /** 頭像焦點,給 object-position 用(值由整數欄位組成,不會有 CSS 注入)。 */
    public function avatarPosition(): string
    {
        return (int) ($this->avatar_pos_x ?? 50).'% '.(int) ($this->avatar_pos_y ?? 50).'%';
    }

    /** 橫幅焦點,給 background-position 用。 */
    public function bannerPosition(): string
    {
        return (int) ($this->banner_pos_x ?? 50).'% '.(int) ($this->banner_pos_y ?? 50).'%';
    }

    /**
     * 這個使用者選的個人頁主題(色碼組)。存的是主題代碼,查 config/profile 得到
     * 白名單裡的色碼 —— 所以顯示端永遠只會輸出定義好的顏色,不會有 CSS 注入。
     *
     * @return array{name:string, accent:string, from:string, to:string}
     */
    public function themeMeta(): array
    {
        $themes = (array) config('profile.themes', []);
        $key = $this->profile_theme;

        if (! isset($themes[$key])) {
            $key = config('profile.default_theme', 'rose');
        }

        return $themes[$key] ?? ['name' => '', 'accent' => '#f43f5e', 'from' => '#f43f5e', 'to' => '#fb7185'];
    }

    /** 名字的首字,給沒有頭像時的預設頭像用。 */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim((string) $this->name), 0, 1)) ?: '?';
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
