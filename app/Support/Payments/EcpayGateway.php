<?php

namespace App\Support\Payments;

use App\Models\PaymentOrder;
use App\Support\Pricing;

/**
 * 綠界 ECPay AioCheckOut V5.
 *
 * Behaviour is unchanged from when this lived inline in PremiumController —
 * same CheckMacValue algorithm, same validation order, same response strings.
 */
class EcpayGateway implements PaymentGateway
{
    /**
     * ECPay publishes these in its own integration docs, so a CheckMacValue
     * built with them proves nothing: anyone can compute a valid one and post a
     * "paid" callback. Treat them as never live.
     */
    private const TEST_MERCHANTS = ['3002607', '2000132', '2000214'];

    private const TEST_HASH_KEY = 'pwFHCqoQZGmho4w6';

    private const TEST_HASH_IV = 'EkRm7iFT261dpevs';

    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'ecpay';
    }

    public function isLive(): bool
    {
        // Off production the sandbox is exactly what we want.
        if (! app()->environment('production')) {
            return true;
        }

        return ! ($this->config['is_sandbox'] ?? true)
            && ! in_array((string) ($this->config['merchant_id'] ?? ''), self::TEST_MERCHANTS, true)
            && ($this->config['hash_key'] ?? '') !== self::TEST_HASH_KEY
            && ($this->config['hash_iv'] ?? '') !== self::TEST_HASH_IV
            && str_contains((string) ($this->config['service_url'] ?? ''), 'payment.ecpay.com.tw');
    }

    /** TotalAmount only accepts whole New Taiwan dollars. */
    public function supportedCurrencies(): array
    {
        return ['TWD'];
    }

    public function checkout(PaymentOrder $order, string $itemName): CheckoutForm
    {
        $params = [
            'MerchantID' => $this->config['merchant_id'],
            'MerchantTradeNo' => $order->order_no,
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) Pricing::fromMinor($order->amount, $order->currency),
            'TradeDesc' => '枕邊遊戲 Premium 會員',
            'ItemName' => $itemName,
            'ReturnURL' => route('premium.callback'),
            'ClientBackURL' => route('premium.index'),
            'OrderResultURL' => route('premium.result'),
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
        ];

        $params['CheckMacValue'] = $this->checkMacValue($params);

        return new CheckoutForm($this->config['service_url'], $params);
    }

    public function verifyCallback(array $payload): CallbackResult
    {
        $received = $payload['CheckMacValue'] ?? '';
        $forMac = $payload;
        unset($forMac['CheckMacValue']);

        if (strtoupper((string) $received) !== strtoupper($this->checkMacValue($forMac))) {
            return CallbackResult::rejected('0|ERR_MAC');
        }

        if (($payload['MerchantID'] ?? '') !== $this->config['merchant_id']) {
            return CallbackResult::rejected('0|ERR_MERCHANT');
        }

        $orderNo = (string) ($payload['MerchantTradeNo'] ?? '');

        if ($orderNo === '') {
            return CallbackResult::rejected('0|ERR_ORDER');
        }

        if ((int) ($payload['RtnCode'] ?? 0) !== 1) {
            return CallbackResult::failed($orderNo, '1|OK');
        }

        // ECPay reports whole TWD, and TWD has no minor unit, so the figure is
        // already in minor units. A gateway settling USD would need *100 here.
        $amountMinor = (int) ($payload['TradeAmt'] ?? $payload['TotalAmount'] ?? 0);

        return CallbackResult::paid($orderNo, $payload['TradeNo'] ?? null, '1|OK', $amountMinor);
    }

    public function orderNoFromResult(array $payload): ?string
    {
        return $payload['MerchantTradeNo'] ?? null;
    }

    /**
     * ECPay's signature: sort keys case-insensitively, wrap with HashKey/HashIV,
     * URL-encode, lowercase, then undo the encoding for a specific set of
     * characters before hashing. The replacements are not optional — .NET's
     * UrlEncode leaves these as-is and the spec was written against it.
     */
    private function checkMacValue(array $params): string
    {
        uksort($params, 'strcasecmp');

        $str = 'HashKey='.$this->config['hash_key'];
        foreach ($params as $key => $value) {
            $str .= "&{$key}={$value}";
        }
        $str .= '&HashIV='.$this->config['hash_iv'];

        $str = strtolower(urlencode($str));
        $str = str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            $str
        );

        return strtoupper(hash('sha256', $str));
    }
}
