<?php

namespace App\Listeners;

use App\Notifications\SiteMessage;
use App\Support\PremiumAccess;
use Illuminate\Auth\Events\Login;

/**
 * 推廣期間,使用者登入時在通知裡告訴他「現在全部免費」—— 每個人只送一次。
 *
 * 掛在 Login 事件上,所以密碼登入、Google 登入、記住我自動登入都算。推廣關掉
 * (PREMIUM_PROMO=false 或過了 PREMIUM_PROMO_UNTIL)之後就不再送。
 * 已經有一則 promo 通知的人不重送 —— 不然每次登入鈴鐺都多一則一樣的。
 *
 * 由 Laravel 的 listener 自動探索註冊(handle 的型別就是事件)。
 */
class SendPromoNotice
{
    public function handle(Login $event): void
    {
        if (! PremiumAccess::promoActive()) {
            return;
        }

        $user = $event->user;
        if (! method_exists($user, 'notifications')
            || $user->notifications()->where('data->kind', SiteMessage::KIND_PROMO)->exists()) {
            return;
        }

        $user->notify(SiteMessage::promo($user->locale ?: app()->getLocale()));
    }
}
