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
