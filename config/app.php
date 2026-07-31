<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'zh_TW'),

    /*
     * 站台的主語系 —— 「這個網站本來是什麼語言」,與「這個請求正在用什麼語言」
     * 是兩件事。
     *
     * 為什麼不能直接用上面的 'locale':Laravel 的 App::setLocale() 內部會執行
     * config(['app.locale' => $locale]),也就是**把這個設定值改掉**。SetLocale
     * 中介層一跑,config('app.locale') 就變成當前請求的語系,再也讀不回原本的預設。
     *
     * 這個 bug 真的發生過,而且症狀很隱蔽:
     *   - schema.org 的 Organization @id 四個語系各產生一個,同一個站被當成四個實體
     *   - sitemap 的 x-default 指向當前語系而不是主語系
     *   - LocaleHelper::pickTranslation() 的「當前語系就是主語系嗎」永遠成立,
     *     於是資料庫裡的翻譯欄位從來沒有被讀出來過
     * 三者都不會報錯,只是安靜地不對。
     */
    'default_locale' => env('APP_DEFAULT_LOCALE', 'zh_TW'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'zh_TW'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'zh_TW'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales (i18n)
    |--------------------------------------------------------------------------
    |
    | List of locales available in this app. Used by views, controllers,
    | and the language switcher. The URL prefix is mapped separately in
    | config/laravellocalization.php.
    |
    */

    /*
    | `ready=true` means the locale's translations are reviewed and we expose
    | it via <link rel="alternate" hreflang> and the per-locale sitemap. When
    | a locale is `ready=false`, the URL still works (falls back to zh_TW) but
    | we omit it from hreflang/sitemap to avoid duplicate-content penalties.
    */
    'available_locales' => [
        'zh_TW' => ['name' => '繁體中文', 'native' => '繁體中文', 'prefix' => 'tw', 'hreflang' => 'zh-TW', 'ready' => true],
        'en' => ['name' => 'English',  'native' => 'English',  'prefix' => 'en', 'hreflang' => 'en',    'ready' => true],
        'zh_CN' => ['name' => '簡體中文', 'native' => '简体中文', 'prefix' => 'cn', 'hreflang' => 'zh-CN', 'ready' => true],
        'ja' => ['name' => '日本語',   'native' => '日本語',   'prefix' => 'jp', 'hreflang' => 'ja',    'ready' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
