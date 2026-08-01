<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily 9 AM Asia/Taipei: send reminder mail for capsules unlocking today
Schedule::command('capsule:send-reminders')
    ->dailyAt('09:00')
    ->timezone('Asia/Taipei');

/*
 * 瀏覽紀錄只留 180 天。這張表每天都在長,而且它回答的問題(現在的動線如何)
 * 本來就只看得到近期 —— 留著兩年前的資料只會讓後台查詢愈來愈慢、備份愈來愈大。
 * 用 chunk 刪:SQLite 一次刪大量列會鎖住整個檔案,連前台都會卡住。
 */
Schedule::call(function () {
    $cutoff = now()->subDays(180);

    do {
        $deleted = App\Models\PageView::where('created_at', '<', $cutoff)
            ->limit(5000)->delete();
    } while ($deleted > 0);
})->dailyAt('04:30')->timezone('Asia/Taipei')->name('prune-page-views');
