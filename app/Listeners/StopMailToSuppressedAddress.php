<?php

namespace App\Listeners;

use App\Models\EmailSuppression;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

/**
 * 收件地址在抑制清單裡就不要寄。
 *
 * 掛在 MessageSending 而不是各個寄信點:驗證信、密碼重設、膠囊提醒各自檢查的話,
 * 遲早有一條路徑忘了檢查,而那條路徑正好會是把退信率推上去的那條。這裡是所有
 * 寄信的唯一出口,回傳 false 就取消這一封。
 *
 * 為什麼要擋:一個已知會硬退信的地址每被重試一次,退信率就再高一點,而退信率
 * 是 SES 停權的主要依據。已經退過的地址不會因為多寄幾次就變得寄得到。
 */
class StopMailToSuppressedAddress
{
    public function handle(MessageSending $event): bool
    {
        foreach ($event->message->getTo() as $address) {
            if (EmailSuppression::isSuppressed($address->getAddress())) {
                Log::info('Mail suppressed', ['to' => $address->getAddress()]);

                return false;   // false = 取消寄送
            }
        }

        return true;
    }
}
