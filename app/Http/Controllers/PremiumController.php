<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Support\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PremiumController extends Controller
{
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
            'gatewayLive' => $this->gatewayIsLive(),
        ]);
    }

    /**
     * ECPay publishes these merchant/key values in its own integration docs, so
     * a CheckMacValue built with them proves nothing — anyone can compute a
     * valid one and POST it to the callback. Treating them as usable in
     * production would hand out premium for free.
     */
    private const ECPAY_TEST_MERCHANTS = ['3002607', '2000132', '2000214'];

    private const ECPAY_TEST_HASH_KEY = 'pwFHCqoQZGmho4w6';

    private const ECPAY_TEST_HASH_IV = 'EkRm7iFT261dpevs';

    /** False when the configured gateway must not be trusted with real money. */
    private function gatewayIsLive(): bool
    {
        // Off production, the sandbox is exactly what we want.
        if (! app()->environment('production')) {
            return true;
        }

        $e = config('ecpay');

        return ! ($e['is_sandbox'] ?? true)
            && ! in_array((string) ($e['merchant_id'] ?? ''), self::ECPAY_TEST_MERCHANTS, true)
            && ($e['hash_key'] ?? '') !== self::ECPAY_TEST_HASH_KEY
            && ($e['hash_iv'] ?? '') !== self::ECPAY_TEST_HASH_IV
            && str_contains((string) ($e['service_url'] ?? ''), 'payment.ecpay.com.tw');
    }

    public function checkout(Request $request)
    {
        // Refuse to start a payment we cannot verify. 503 rather than a silent
        // redirect so a misconfigured deploy is loud instead of quietly free.
        abort_unless($this->gatewayIsLive(), 503, __('premium.err_gateway_not_live'));

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
        ]);

        // Build ECPay form data
        //
        // 綠界的 TotalAmount 只吃「新台幣整數元」,所以非台幣的方案根本送不出去。
        // 這裡明確擋掉而不是默默送出一個錯的金額 —— 靜默送錯會變成使用者付了
        // US$34.99 的心理價、實際被收 3499 元台幣這種等級的事故。
        // 換到支援多幣別的金流(CCBill / SegPay 之類)之後,這段會整個被 driver 取代。
        $ecpay = config('ecpay');
        abort_if(
            $currency !== 'TWD',
            503,
            "目前串接的金流只支援新台幣,無法用 {$currency} 結帳。請見 config/premium.php 的說明。"
        );

        $params = [
            'MerchantID' => $ecpay['merchant_id'],
            'MerchantTradeNo' => $order->order_no,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) Pricing::fromMinor($minorAmount, $currency),
            'TradeDesc' => '枕邊遊戲 Premium 會員',
            'ItemName' => __('premium.plan_'.$planKey),
            'ReturnURL' => route('premium.callback'),
            'ClientBackURL' => route('premium.index'),
            'OrderResultURL' => route('premium.result'),
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
        ];

        // Generate CheckMacValue
        $params['CheckMacValue'] = $this->generateCheckMacValue($params, $ecpay['hash_key'], $ecpay['hash_iv']);

        return view('premium.checkout', [
            'params' => $params,
            'actionUrl' => $ecpay['service_url'],
        ]);
    }

    public function callback(Request $request)
    {
        // The signature below is only meaningful with private credentials. With
        // the published test key anyone could forge a paid callback, so reject
        // before looking at anything the caller sent.
        if (! $this->gatewayIsLive()) {
            return response('0|ERR_GATEWAY');
        }

        $data = $request->all();
        $ecpay = config('ecpay');

        // Verify CheckMacValue
        $receivedMac = $data['CheckMacValue'] ?? '';
        $paramsForMac = $data;
        unset($paramsForMac['CheckMacValue']);

        $expectedMac = $this->generateCheckMacValue($paramsForMac, $ecpay['hash_key'], $ecpay['hash_iv']);

        if (strtoupper($receivedMac) !== strtoupper($expectedMac)) {
            return response('0|ERR_MAC');
        }

        // Verify MerchantID matches our config
        if (($data['MerchantID'] ?? '') !== $ecpay['merchant_id']) {
            return response('0|ERR_MERCHANT');
        }

        $orderNo = $data['MerchantTradeNo'] ?? '';
        $order = PaymentOrder::where('order_no', $orderNo)->first();

        if (! $order) {
            return response('0|ERR_ORDER');
        }

        // Verify amount matches local order.
        // order->amount 是最小單位,綠界回傳的是元。台幣沒有小數,兩者相等;而
        // checkout 已經擋掉非台幣的結帳,所以這裡不需要換算。換金流時要一起改。
        $callbackAmount = (int) ($data['TradeAmt'] ?? $data['TotalAmount'] ?? 0);
        if ($callbackAmount !== (int) $order->amount) {
            return response('0|ERR_AMOUNT');
        }

        $rtnCode = (int) ($data['RtnCode'] ?? 0);

        if ($rtnCode === 1) {
            $alreadyPaid = false;

            DB::transaction(function () use ($order, $data, &$alreadyPaid) {
                // Lock the order row to prevent concurrent callback race
                $locked = PaymentOrder::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->isPaid()) {
                    $alreadyPaid = true;

                    return;
                }

                $locked->update([
                    'status' => 'paid',
                    'trade_no' => $data['TradeNo'] ?? null,
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

        return response('1|OK');
    }

    public function result(Request $request)
    {
        $orderNo = $request->input('MerchantTradeNo');
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

    private function generateCheckMacValue(array $params, string $hashKey, string $hashIV): string
    {
        // Sort by key (case-insensitive per ECPay spec)
        uksort($params, 'strcasecmp');

        // Build query string
        $str = "HashKey={$hashKey}";
        foreach ($params as $key => $value) {
            $str .= "&{$key}={$value}";
        }
        $str .= "&HashIV={$hashIV}";

        // ECPay-specific URL encode then lowercase
        $str = strtolower(urlencode($str));

        // ECPay custom character replacements
        $str = str_replace('%2d', '-', $str);
        $str = str_replace('%5f', '_', $str);
        $str = str_replace('%2e', '.', $str);
        $str = str_replace('%21', '!', $str);
        $str = str_replace('%2a', '*', $str);
        $str = str_replace('%28', '(', $str);
        $str = str_replace('%29', ')', $str);

        // SHA256
        return strtoupper(hash('sha256', $str));
    }
}
