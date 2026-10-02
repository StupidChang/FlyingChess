<?php

namespace App\Notifications;

use App\Support\LocaleHelper;
use Illuminate\Notifications\Notification;

/**
 * 站內通知(右上角的鈴鐺)。只走 database channel,不寄信。
 *
 * 文字在送出的那一刻就定稿,存的是**成品**而不是翻譯 key:後台手寫的通知本來
 * 就只有一種語言;系統通知(歡迎訊息)則用收件人自己的語系渲染好再存。
 * 存 key 的話,之後改文案會連已經送出的舊通知一起改掉。
 *
 * $url 只收站內的相對路徑(見 safeUrl),通知面板的連結不能被拿來導去外站。
 */
class SiteMessage extends Notification
{
    public const KIND_ADMIN = 'admin';

    public const KIND_WELCOME = 'welcome';

    public const KIND_PROMO = 'promo';

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $url = null,
        public readonly string $kind = self::KIND_ADMIN,
    ) {}

    /** 歡迎通知:用收件人註冊時的語系渲染。 */
    public static function welcome(string $locale): self
    {
        return new self(
            trans('notifications.welcome_title', [], $locale),
            trans('notifications.welcome_body', [], $locale),
            '/'.(LocaleHelper::localeToPrefix($locale) ?? 'tw').'/game-hall',
            self::KIND_WELCOME,
        );
    }

    /** 推廣期間全部開放的通知(見 App\Listeners\SendPromoNotice)。 */
    public static function promo(string $locale): self
    {
        return new self(
            trans('notifications.promo_title', [], $locale),
            trans('notifications.promo_body', [], $locale),
            '/'.(LocaleHelper::localeToPrefix($locale) ?? 'tw').'/game-hall',
            self::KIND_PROMO,
        );
    }

    /** 站內相對路徑才收;「//evil.com」這種協定相對網址也擋掉。 */
    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => self::safeUrl($this->url),
            'kind' => $this->kind,
        ];
    }
}
