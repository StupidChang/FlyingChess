<?php

/* Password broker 的狀態訊息。PasswordResetController 失敗時會直接 __($status),
   少了這個檔案使用者看到的是「passwords.token」。 */

return [
    'reset' => '密碼已經重設好了。',
    'sent' => '重設密碼的連結已寄到你的信箱。',
    'throttled' => '請稍等一下再試。',
    'token' => '這個重設密碼連結已失效或過期,請重新申請一次。',
    'user' => '找不到使用這個 Email 的帳號。',
];
