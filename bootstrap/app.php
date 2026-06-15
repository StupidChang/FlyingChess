<?php

use App\Http\Middleware\AgeVerification;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsurePremium;
use App\Http\Middleware\RedirectUnprefixedUrl;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'age.verify' => AgeVerification::class,
            'premium' => EnsurePremium::class,
            'admin' => EnsureAdmin::class,
            'not.banned' => EnsureNotBanned::class,
            'set.locale' => SetLocale::class,
            'redirect.unprefixed' => RedirectUnprefixedUrl::class,
        ]);

        // The locale cookie is a UI preference (not sensitive); skip encryption
        // so RedirectUnprefixedUrl can read it before EncryptCookies runs, and
        // so the front end can read it via document.cookie for the language switcher.
        $middleware->encryptCookies(except: ['locale']);

        // Order matters: RedirectUnprefixedUrl 301s legacy non-prefixed URLs
        // before AgeVerification renders the age gate, avoiding wasted renders.
        $middleware->prepend(RedirectUnprefixedUrl::class);
        $middleware->append(AgeVerification::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
