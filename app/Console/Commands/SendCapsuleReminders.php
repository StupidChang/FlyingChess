<?php

namespace App\Console\Commands;

use App\Models\TimeCapsule;
use App\Support\LocaleHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCapsuleReminders extends Command
{
    protected $signature = 'capsule:send-reminders';

    protected $description = 'Email capsule owners on the day their time capsule unlocks';

    public function handle(): int
    {
        $today = Carbon::today();

        $capsules = TimeCapsule::whereNotNull('notify_email')
            ->whereNotNull('sealed_at')
            ->where('reminder_sent', false)
            ->whereDate('open_at', $today->toDateString())
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($capsules as $capsule) {
            try {
                $localePrefix = LocaleHelper::localeToPrefix(LocaleHelper::defaultLocale()) ?? 'tw';
                $url = url(route('time-capsule.show', [
                    'locale'    => $localePrefix,
                    'shareCode' => $capsule->share_code,
                ], false));
                $body = "你的時間膠囊「{$capsule->title}」今天可以開封了。\n\n"
                      . "點開連結回去看看：\n{$url}\n\n"
                      . "— 枕邊遊戲 PillowPlay";

                Mail::raw($body, function ($message) use ($capsule) {
                    $message->to($capsule->notify_email)
                            ->subject("📦 時間膠囊「{$capsule->title}」今天開封！");
                });

                $capsule->update(['reminder_sent' => true]);
                $sent++;
            } catch (Throwable $e) {
                // Mail config missing or transport failed — degrade gracefully.
                Log::warning('Capsule reminder mail failed', [
                    'capsule_id' => $capsule->id,
                    'error'      => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("Capsule reminders: sent {$sent}, failed {$failed}");
        return self::SUCCESS;
    }
}
