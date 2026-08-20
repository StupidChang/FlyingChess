<?php

/*
 * 個人頁的可選主題。
 *
 * 使用者只能存「主題代碼」(themes 的鍵),顯示時由這裡查出色碼再輸出 —— 所以
 * 永遠只會印出白名單裡的顏色,使用者無法塞任意 CSS 進來。要加新主題就在這裡加一筆。
 *
 * 每個主題:
 *   name   選色器上顯示的名字
 *   accent 主色(重點、連結、標籤)
 *   from/to  個人頁頂部橫幅的漸層兩端
 */
return [
    /*
     * 頭像 / 橫幅存哪個 disk(見 config/filesystems.php)。
     *   - 沒設定時:填了 R2_BUCKET 就自動走 'r2',否則走本機 'public'。
     *   - 也可以用 PROFILE_UPLOAD_DISK 明確指定。
     * 這樣「接上 R2」只要在 .env 填 R2 憑證即可,不必改程式。
     */
    'upload_disk' => env('PROFILE_UPLOAD_DISK') ?: (env('R2_BUCKET') ? 'r2' : 'public'),

    'default_theme' => 'rose',

    'themes' => [
        'rose' => ['name' => '玫瑰', 'accent' => '#f43f5e', 'from' => '#f43f5e', 'to' => '#fb7185'],
        'amber' => ['name' => '琥珀', 'accent' => '#e0a83c', 'from' => '#d9a441', 'to' => '#f0c674'],
        'violet' => ['name' => '紫羅蘭', 'accent' => '#8b7cf6', 'from' => '#7c6cf0', 'to' => '#a5b4fc'],
        'teal' => ['name' => '青碧', 'accent' => '#2dd4bf', 'from' => '#14b8a6', 'to' => '#5eead4'],
        'sky' => ['name' => '天藍', 'accent' => '#38bdf8', 'from' => '#0ea5e9', 'to' => '#7dd3fc'],
        'coral' => ['name' => '珊瑚', 'accent' => '#fb7185', 'from' => '#fb923c', 'to' => '#fb7185'],
        'emerald' => ['name' => '翡翠', 'accent' => '#34d399', 'from' => '#10b981', 'to' => '#6ee7b7'],
        'plum' => ['name' => '梅紫', 'accent' => '#d074f0', 'from' => '#c084fc', 'to' => '#e879f9'],
    ],
];
