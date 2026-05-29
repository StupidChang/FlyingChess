<?php

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
            'age.verify' => \App\Http\Middleware\AgeVerification::class,
            'premium' => \App\Http\Middleware\EnsurePremium::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'redirect.unprefixed' => \App\Http\Middleware\RedirectUnprefixedUrl::class,
        ]);

        // The locale cookie is a UI preference (not sensitive); skip encryption
        // so RedirectUnprefixedUrl can read it before EncryptCookies runs, and
        // so the front end can read it via document.cookie for the language switcher.
        $middleware->encryptCookies(except: ['locale']);

        // Order matters: RedirectUnprefixedUrl 301s legacy non-prefixed URLs
        // before AgeVerification renders the age gate, avoiding wasted renders.
        $middleware->prepend(\App\Http\Middleware\RedirectUnprefixedUrl::class);
        $middleware->append(\App\Http\Middleware\AgeVerification::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
