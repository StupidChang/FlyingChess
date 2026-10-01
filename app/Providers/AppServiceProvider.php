<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\LocaleHelper;
use App\Support\Payments\PaymentGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Throwable;

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
        $this->mergePricingOverrides();
        $this->registerRateLimiters();

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

    /**
     * 具名的節流器。
     *
     * 為什麼需要:`throttle:40,1` 這種**匿名**寫法的計數器 key 是
     * `sha1(domain|ip)` —— 裡面沒有路由。也就是說站上每一條匿名節流路由(擲骰、
     * 輪詢 state、抽卡、送出測驗……)全部共用同一個 per-IP 計數器,只是各自比對
     * 不同的門檻。實際後果:一場飛行棋每 2 秒輪詢一次 state(30 次/分)再加上
     * 幾個動作,一分鐘內輕鬆超過 40 —— 然後那位玩家(以及同一個 NAT 出口的
     * 所有人)去點五個迷你遊戲頁就會拿到 429。Googlebot 也一樣,而那五頁都在
     * sitemap 裡,爬到 429 只會讓它降低爬取速率。
     *
     * 具名節流器可以自己決定 key,所以這裡把 bucket 縮到「這一頁 + 這個 IP」。
     * 防題庫被枚舉的意圖沒有變:同一頁一分鐘重載 40 次仍然是硬牆,而真人玩一場
     * 只會載一次。跨語系刻意共用一個 bucket —— 題庫是同一批、只是換了翻譯,
     * 不該讓輪流換語系就拿到四倍額度。
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('minigame-page', function (Request $request) {
            return Limit::perMinute(40)->by('minigame-page|'.$request->route()->uri().'|'.$request->ip());
        });

        /* 站內回報:一小時 5 則。擋到的時候不丟 429 錯誤頁 —— 那會把使用者剛打好的
           一大段字整個丟掉,而會連送五則的通常是真的有很多話要說的人。改成回到表單、
           內容原樣留著,告訴他多久之後可以再送。 */
        RateLimiter::for('feedback', function (Request $request) {
            return Limit::perHour(5)->by('feedback|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                    return back()
                        ->withInput($request->except('website'))
                        ->withErrors(['message' => __('feedback.throttled', ['minutes' => $minutes])]);
                });
        });
    }

    /**
     * 把後台改過的定價覆寫進 runtime config。
     *
     * 價格「結構」(有哪些幣別、方案幾天)仍以 config/premium.php 為準;這裡只把
     * 管理員在 /admin/pricing 存進 settings 表的**金額**與**語系→幣別對應**疊上去。
     * 因為只動既有的鍵、且以 config 定義的幣別/方案為白名單,後台填錯不會污染設定。
     *
     * 放在 boot():即使 config 被 cache,boot 每個請求都會跑,所以覆寫照樣生效;
     * DB 讀取有 Setting 的快取,不會每個請求打一次 DB。整段包在 try 裡 —— migrate
     * 之前(settings 表還沒建)不該讓整站起不來。
     */
    private function mergePricingOverrides(): void
    {
        try {
            $override = Setting::getValue('pricing');
        } catch (Throwable) {
            return;   // 資料表還沒建 / DB 尚未就緒
        }

        if (! is_array($override)) {
            return;
        }

        $knownCurrencies = array_keys((array) config('premium.currencies', []));

        // 金額:plans.{plan}.amounts.{currency}
        foreach ((array) ($override['amounts'] ?? []) as $plan => $byCurrency) {
            if (! is_array(config("premium.plans.{$plan}"))) {
                continue;   // 不是已定義的方案就跳過
            }
            foreach ((array) $byCurrency as $cur => $amount) {
                if (! in_array($cur, $knownCurrencies, true) || ! is_numeric($amount)) {
                    continue;
                }
                config(["premium.plans.{$plan}.amounts.{$cur}" => 0 + $amount]);
            }
        }

        // 語系 → 幣別。只收「值是已定義幣別」的項目。
        if (is_array($override['locale_currency'] ?? null)) {
            $map = [];
            foreach ($override['locale_currency'] as $locale => $cur) {
                if (in_array($cur, $knownCurrencies, true)) {
                    $map[$locale] = $cur;
                }
            }
            config(['premium.locale_currency' => $map]);
        }
    }
}
