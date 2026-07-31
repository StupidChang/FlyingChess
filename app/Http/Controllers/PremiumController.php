<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Support\Payments\PaymentGateway;
use App\Support\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PremiumController extends Controller
{
    /** Resolved from config/payments.php — see AppServiceProvider::register(). */
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $currency = Pricing::currency();

        // 方案的展示資料全部在這裡組好,Blade 只負責排版 —— 省下的百分比之類的
        // 算式放在 view 裡,四個語系的模板就會各自抄一份。
        $plans = array_map(fn (string $key) => [
            'key' => $key,
            'name' => __('premium.plan_'.$key),
            'period' => __('premium.period_'.$key),
            'price' => Pricing::format($key, $currency),
            'days' => Pricing::days($key),
            'saving' => Pricing::savingPercent($key, $currency),
            'recommended' => $key === Pricing::defaultPlan(),
        ], Pricing::planKeys());

        return view('premium.index', [
            'plans' => $plans,
            'currency' => $currency,
            'entryPrice' => Pricing::entryPrice($currency),
            'isPremium' => $user && $user->isPremium(),
            'expiresAt' => $user?->premium_expires_at,
            'gatewayLive' => $this->gateway->isLive(),
        ]);
    }

    public function checkout(Request $request)
    {
        // Refuse to start a payment we cannot verify. 503 rather than a silent
        // redirect so a misconfigured deploy is loud instead of quietly free.
        abort_unless($this->gateway->isLive(), 503, __('premium.err_gateway_not_live'));

        // 排除七日猶豫期的前提是「消費者事先明示同意」。前端的 required 只是
        // 提示,真正的證明要靠伺服器端拒絕沒有同意的請求。
        $request->validate(
            ['consent' => 'accepted'],
            ['consent.accepted' => __('premium.err_consent_required')],
        );

        $user = $request->user();

        // 方案由表單送上來,但金額一律回頭查 config —— 絕不能相信前端送的價格。
        $planKey = (string) $request->input('plan', Pricing::defaultPlan());
        if (! Pricing::exists($planKey)) {
            $planKey = Pricing::defaultPlan();
        }

        $currency = Pricing::currency();
        $minorAmount = Pricing::minorAmount($planKey, $currency);

        // Create new order every time
        $orderNo = 'FC'.date('YmdHis').strtoupper(Str::random(4));

        $order = PaymentOrder::create([
            'user_id' => $user->id,
            'order_no' => $orderNo,
            'amount' => $minorAmount,   // 最小單位,不是元
            'currency' => $currency,
            'plan' => $planKey,
            'status' => 'pending',
            // 存證用:排除猶豫期的前提是「事先同意」,要能舉證發生在付款之前。
            'consented_at' => now(),
        ]);

        // 金額的幣別必須是這家金流真的能結算的,否則就是「顯示 US$34.99、實際
        // 扣 3499 元台幣」這種等級的事故。明確擋掉而不是默默送出。
        abort_if(
            ! in_array($currency, $this->gateway->supportedCurrencies(), true),
            503,
            "目前串接的金流({$this->gateway->name()})不支援 {$currency} 結帳。請見 config/premium.php 的說明。"
        );

        $form = $this->gateway->checkout($order, __('premium.plan_'.$planKey));

        return view('premium.checkout', [
            'params' => $form->params,
            'actionUrl' => $form->actionUrl,
            'method' => $form->method,
        ]);
    }

    public function callback(Request $request)
    {
        // The signature below is only meaningful with private credentials. With
        // the published test key anyone could forge a paid callback, so reject
        // before looking at anything the caller sent.
        if (! $this->gateway->isLive()) {
            return response('0|ERR_GATEWAY');
        }

        $result = $this->gateway->verifyCallback($request->all());

        if (! $result->valid) {
            return response($result->response);
        }

        $order = PaymentOrder::where('order_no', $result->orderNo)->first();

        if (! $order) {
            return response('0|ERR_ORDER');
        }

        // The signature proves the callback came from the gateway; it does not
        // prove the customer paid what we asked. Compare against the stored
        // order before granting anything.
        if ($result->paid && $result->amountMinor !== (int) $order->amount) {
            return response('0|ERR_AMOUNT');
        }

        if ($result->paid) {
            $alreadyPaid = false;

            DB::transaction(function () use ($order, $result, &$alreadyPaid) {
                // Lock the order row to prevent concurrent callback race
                $locked = PaymentOrder::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->isPaid()) {
                    $alreadyPaid = true;

                    return;
                }

                $locked->update([
                    'status' => 'paid',
                    'trade_no' => $result->tradeNo,
                ]);

                // Lock user row to prevent concurrent renewal from losing updates
                $user = User::where('id', $locked->user_id)->lockForUpdate()->first();
                $now = now();
                $currentExpiry = $user->premium_expires_at;

                // Renewal: max(current_expiry, now) + 30 days
                $base = ($currentExpiry && $currentExpiry->isFuture())
                    ? $currentExpiry
                    : $now;

                // 天數看訂單記下的方案(月 30 / 年 365),不是 config 當下的值 ——
                // 否則日後調整方案天數,舊訂單的補發或重試會給錯。
                $user->update([
                    'premium_expires_at' => $base->copy()->addDays($locked->durationDays()),
                ]);
            });
        } else {
            // Idempotent: already paid orders stay paid (concurrency-safe)
            DB::transaction(function () use ($order) {
                $locked = PaymentOrder::where('id', $order->id)->lockForUpdate()->first();
                if ($locked && ! $locked->isPaid()) {
                    $locked->update(['status' => 'failed']);
                }
            });
        }

        // The exact acknowledgement string is provider-specific — gateways use
        // it to decide whether to retry the callback.
        return response($result->response);
    }

    public function result(Request $request)
    {
        $orderNo = $this->gateway->orderNoFromResult($request->all());
        $order = $orderNo ? PaymentOrder::where('order_no', $orderNo)->first() : null;

        // The gateway posts here without a user session, but an anonymous
        // visitor must not be able to inspect another customer's order.
        if ($order && (! $request->user() || $order->user_id !== $request->user()->id)) {
            $order = null;
        }

        return view('premium.result', [
            'order' => $order,
            'isPremium' => $request->user()?->fresh()?->isPremium() ?? false,
        ]);
    }
}
