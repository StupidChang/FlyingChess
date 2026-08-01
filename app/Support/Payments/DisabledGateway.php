<?php

namespace App\Support\Payments;

use App\Models\PaymentOrder;
use RuntimeException;

/**
 * 沒有金流商。這是目前的預設值,而且是刻意的。
 *
 * 綠界(ECPay)的實作已經整份移除:台灣金流的特約商店條款基本都排除情色類,
 * 本站有年齡閘與明確的成人內容,過不了審是一回事,過了之後被抽查停權更麻煩 ——
 * 款項可能被凍結。方向是 CCBill / SegPay 這類成人產業金流,那時候再寫一個新的
 * driver 加進 config/payments.php 即可,PremiumController 不用動。
 *
 * 在那之前,這個 driver 讓「沒有金流」變成一個明確的狀態,而不是靠測試憑證
 * 加上一堆 if 去擋:isLive() 永遠是 false,所有付款入口都不會出現,結帳路由
 * 直接 404,任何人都不可能誤觸到一顆會真的送出交易的按鈕。
 */
class DisabledGateway implements PaymentGateway
{
    /** 不需要憑證,但保留簽章讓 AppServiceProvider 一視同仁地 new 出來。 */
    public function __construct(array $config = []) {}

    public function name(): string
    {
        return 'disabled';
    }

    public function isLive(): bool
    {
        return false;
    }

    public function supportedCurrencies(): array
    {
        return [];
    }

    /*
     * 下面三個方法不應該被呼叫到 —— 呼叫端在動作前都會先問 isLive()。
     * 真的被呼叫代表某條路徑漏了檢查,所以這裡故意炸掉而不是安靜回傳空值:
     * 跟金錢有關的漏檢,寧可在測試環境爆一個例外,也不要在正式環境默默吞掉。
     */

    public function checkout(PaymentOrder $order, string $itemName): CheckoutForm
    {
        throw new RuntimeException('No payment gateway is configured; checkout must be gated on isLive().');
    }

    public function verifyCallback(array $payload): CallbackResult
    {
        throw new RuntimeException('No payment gateway is configured; callbacks must be gated on isLive().');
    }

    public function orderNoFromResult(array $payload): ?string
    {
        return null;
    }
}
