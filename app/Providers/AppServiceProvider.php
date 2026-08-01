<?php

namespace App\Providers;

use App\Support\LocaleHelper;
use App\Support\Payments\PaymentGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolve the configured payment gateway. Swapping providers is a config
        // change plus one new class — see config/payments.php.
        $this->app->singleton(PaymentGateway::class, function () {
            $name = config('payments.default');
            $entry = config("payments.gateways.{$name}");

            if (! $entry) {
                throw new InvalidArgumentException("Unknown payment gateway [{$name}].");
            }

            // config 為 null 代表這個 driver 不需要憑證(目前的 DisabledGateway)。
            // 不能直接丟給 config() —— config(null) 回傳的是整個設定容器。
            $settings = $entry['config'] ? config($entry['config'], []) : [];

            return new $entry['driver']($settings);
        });
    }

    /**
     * Notification URL builders explicitly carry the {locale} parameter so
     * password-reset and email-verification links keep working in queue/console
     * contexts where SetLocale middleware did not run and URL::defaults is empty.
     * Without this, route('password.reset', [...]) would fail with
     * UrlGenerationException because all auth routes now require {locale}.
     */
    public function boot(): void
    {
        // 有沒有可以收錢的金流。付費入口(按鈕、升級連結)一律掛在這個值上,
        // 而不是各自去猜 —— 目前是 DisabledGateway,所以全站不出現任何付款入口。
        // 用 composer 而不是 View::share:避免在 boot 階段就解析 gateway,
        // 將來換成需要連外的 driver 時不會拖慢每一個 console 指令。
        View::composer(['games.lobby', 'boards.templates', 'boards.template-preview'], function ($view) {
            $view->with('purchaseEnabled', app(PaymentGateway::class)->isLive());
        });

        $resetUrl = function ($notifiable, string $token) {
            $prefix = LocaleHelper::localeToPrefix(app()->getLocale())
                ?? LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale());

            return url(route('password.reset', [
                'locale' => $prefix,
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        };

        ResetPassword::createUrlUsing($resetUrl);

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $prefix = LocaleHelper::localeToPrefix(app()->getLocale())
                ?? LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale());

            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'locale' => $prefix,
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        /*
         * Framework defaults are English-only. Both closures run inside
         * Lang::withLocale($notifiable->preferredLocale()), so __() already
         * resolves to the recipient's language — see User::preferredLocale().
         */
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject(__('mail.verify_subject'))
                ->greeting(__('mail.verify_greeting', ['name' => $notifiable->name]))
                ->line(__('mail.verify_line1'))
                ->action(__('mail.verify_action'), $url)
                ->line(__('mail.verify_line2'))
                ->salutation(__('mail.salutation'));
        });

        // NOTE: this callback receives the raw token, not a URL — and setting it
        // makes the framework skip resetUrl(), so createUrlUsing above never
        // fires for mail. Build the link here with the same shared closure.
        ResetPassword::toMailUsing(function ($notifiable, string $token) use ($resetUrl) {
            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject(__('mail.reset_subject'))
                ->greeting(__('mail.reset_greeting', ['name' => $notifiable->name]))
                ->line(__('mail.reset_line1'))
                ->action(__('mail.reset_action'), $resetUrl($notifiable, $token))
                ->line(__('mail.reset_line2', ['count' => $expire]))
                ->line(__('mail.reset_line3'))
                ->salutation(__('mail.salutation'));
        });
    }
}
