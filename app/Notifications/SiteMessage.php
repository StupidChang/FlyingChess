<?php

namespace App\Notifications;

use App\Support\LocaleHelper;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;

/**
 * 站內通知(右上角的鈴鐺)。只走 database channel,不寄信。
 *
 * 後台手寫的通知存的是成品,只有一種語言。系統通知(歡迎、推廣)也會存一份
 * 收件人語系的文字當備援,但**顯示時**改用目前頁面的語系重新翻譯(見 present()),
 * 所以使用者切換網站語言,通知也跟著換。代價是改這幾則的文案時,已經送出的
 * 舊通知會一起變成新文案 —— 對系統公告來說這是想要的結果。
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

    /**
     * 系統通知各自的翻譯 key 與連結的路徑(不含語系前綴)。
     * 這些在**顯示的時候**才翻譯,所以切換網站語言,通知也跟著換語言。
     */
    private const SYSTEM_KINDS = [
        self::KIND_WELCOME => ['notifications.welcome_title', 'notifications.welcome_body', 'game-hall'],
        self::KIND_PROMO => ['notifications.promo_title', 'notifications.promo_body', 'game-hall'],
    ];

    /**
     * 一則通知要顯示的標題、內文、連結。
     *
     * 系統通知(歡迎、推廣)用目前頁面的語系翻譯,連結也換成同語系的網址;
     * 存下來的文字只在翻譯 key 不存在時當備援。後台手寫的通知只有一種語言,
     * 照存的內容顯示。
     *
     * @return array{title: string, body: string, url: ?string}
     */
    public static function present(DatabaseNotification $n): array
    {
        $data = (array) $n->data;
        $kind = $data['kind'] ?? null;

        if (isset(self::SYSTEM_KINDS[$kind])) {
            [$titleKey, $bodyKey, $path] = self::SYSTEM_KINDS[$kind];
            $locale = app()->getLocale();
            $prefix = LocaleHelper::localeToPrefix($locale) ?? 'tw';

            return [
                'title' => trans()->has($titleKey) ? __($titleKey) : (string) ($data['title'] ?? ''),
                'body' => trans()->has($bodyKey) ? __($bodyKey) : (string) ($data['body'] ?? ''),
                'url' => '/'.$prefix.'/'.$path,
            ];
        }

        return [
            'title' => (string) ($data['title'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'url' => self::safeUrl($data['url'] ?? null),
        ];
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
