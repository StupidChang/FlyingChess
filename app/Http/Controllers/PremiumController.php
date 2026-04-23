<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PremiumController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('premium.index', [
            'price' => config('premium.price'),
            'isPremium' => $user && $user->isPremium(),
            'expiresAt' => $user?->premium_expires_at,
        ]);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $amount = config('premium.price');

        // Create new order every time
        $orderNo = 'FC' . date('YmdHis') . strtoupper(Str::random(4));

        $order = PaymentOrder::create([
            'user_id' => $user->id,
            'order_no' => $orderNo,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        // Build ECPay form data
        $ecpay = config('ecpay');
        $params = [
            'MerchantID' => $ecpay['merchant_id'],
            'MerchantTradeNo' => $order->order_no,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => $amount,
            'TradeDesc' => '情侶飛行棋 Premium 會員',
            'ItemName' => '情侶飛行棋 Premium 月費會員',
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

        if (!$order) {
            return response('0|ERR_ORDER');
        }

        // Verify amount matches local order
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
                $user = \App\Models\User::where('id', $locked->user_id)->lockForUpdate()->first();
                $now = now();
                $currentExpiry = $user->premium_expires_at;

                // Renewal: max(current_expiry, now) + 30 days
                $base = ($currentExpiry && $currentExpiry->isFuture())
                    ? $currentExpiry
                    : $now;

                $user->update([
                    'premium_expires_at' => $base->copy()->addDays(config('premium.duration_days')),
                ]);
            });
        } else {
            // Idempotent: already paid orders stay paid (concurrency-safe)
            DB::transaction(function () use ($order) {
                $locked = PaymentOrder::where('id', $order->id)->lockForUpdate()->first();
                if ($locked && !$locked->isPaid()) {
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
