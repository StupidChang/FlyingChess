<?php

namespace App\Providers;

use App\Support\LocaleHelper;
use App\Support\Payments\PaymentGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
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

            return new $entry['driver'](config($entry['config'], []));
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
