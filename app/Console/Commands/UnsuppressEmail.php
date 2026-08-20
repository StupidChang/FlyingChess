<?php

namespace App\Console\Commands;

use App\Models\EmailSuppression;
use Illuminate\Console\Command;

/**
 * 把某個地址從寄信抑制清單移除。
 *
 * 抑制清單原本只由 SES 的退信/客訴 webhook 寫入(見 SesFeedbackController),
 * 在此之前沒有任何解除路徑 —— 一旦某地址被加進去(不論是真的退信,還是被偽造
 * 的通知塞進來),站上就永遠寄不出信給它,含密碼重設與 email 驗證。這個指令就是
 * 那個缺席的恢復開關。
 *
 *   php artisan mail:unsuppress someone@example.com
 */
class UnsuppressEmail extends Command
{
    protected $signature = 'mail:unsuppress {email : 要解除抑制的信箱地址}';

    protected $description = 'Remove an email address from the send-suppression list';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (EmailSuppression::release($email)) {
            $this->info("已解除抑制:{$email}");

            return self::SUCCESS;
        }

        $this->warn("清單中沒有這個地址(不需解除):{$email}");

        return self::SUCCESS;
    }
}
