<?php

/* Password broker 的状态讯息。PasswordResetController 失败时会直接 __($status),
   少了这个文件使用者看到的是「passwords.token」。 */

return [
    'reset' => '密码已经重设好了。',
    'sent' => '重设密码的链接已寄到你的邮箱。',
    'throttled' => '请稍等一下再试。',
    'token' => '这个重设密码链接已失效或过期,请重新申请一次。',
    'user' => '找不到使用这个 Email 的账号。',
];
