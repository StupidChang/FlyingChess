<?php

namespace App\Providers;

use App\Support\LocaleHelper;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $prefix = LocaleHelper::localeToPrefix(app()->getLocale())
                ?? LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale());

            return url(route('password.reset', [
                'locale' => $prefix,
                'token'  => $token,
                'email'  => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $prefix = LocaleHelper::localeToPrefix(app()->getLocale())
                ?? LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale());

            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'locale' => $prefix,
                    'id'     => $notifiable->getKey(),
                    'hash'   => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}
