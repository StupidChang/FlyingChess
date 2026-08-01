<?php

namespace Tests\Support;

use App\Models\PaymentOrder;
use App\Support\Payments\CallbackResult;
use App\Support\Payments\CheckoutForm;
use App\Support\Payments\CheckoutForm as Form;
use App\Support\Payments\PaymentGateway;

/**
 * 一個「已接上、可以收錢」的假金流,只給測試用。
 *
 * 站上目前的預設是 DisabledGateway(見 config/payments.php),結帳路由因此 404。
 * 但結帳本身的規則 —— 金額只能由後端決定、沒有勾同意就不准送出、方案代號不認得
 * 要退回預設 —— 跟「現在用哪一家金流」無關,而且正是接上新金流那天最不能壞的
 * 部分。所以測試自己綁一個 isLive() 為 true 的 driver,不依賴任何廠商的沙箱憑證。
 *
 * 在這之前這幾條測試是靠綠界公開的測試憑證才跑得起來的,等於用「一個外部廠商
 * 剛好還沒改的東西」當測試前提。
 */
class FakeLiveGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function isLive(): bool
    {
        return true;
    }

    public function supportedCurrencies(): array
    {
        return ['TWD', 'USD', 'JPY'];
    }

    public function checkout(PaymentOrder $order, string $itemName): CheckoutForm
    {
        return new Form('https://gateway.test/pay', [
            'order_no' => $order->order_no,
            'amount' => $order->amount,
        ]);
    }

    public function verifyCallback(array $payload): CallbackResult
    {
        return CallbackResult::paid(
            (string) ($payload['order_no'] ?? ''),
            'FAKE-TRADE',
            'OK',
            (int) ($payload['amount'] ?? 0),
        );
    }

    public function orderNoFromResult(array $payload): ?string
    {
        return $payload['order_no'] ?? null;
    }
}
