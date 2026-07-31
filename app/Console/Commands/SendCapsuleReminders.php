<?php

namespace App\Console\Commands;

use App\Models\TimeCapsule;
use App\Support\LocaleHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Localizable;
use Throwable;

class SendCapsuleReminders extends Command
{
    use Localizable;

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
                // Capsules created before the locale column exists have none —
                // fall back to the site default rather than the CLI locale.
                $locale = LocaleHelper::isSupported((string) $capsule->locale)
                    ? $capsule->locale
                    : LocaleHelper::defaultLocale();
                $localePrefix = LocaleHelper::localeToPrefix($locale) ?? 'tw';

                $url = url(route('time-capsule.show', [
                    'locale' => $localePrefix,
                    'shareCode' => $capsule->share_code,
                ], false));

                [$subject, $body] = $this->withLocale($locale, fn () => [
                    __('mail.capsule_subject', ['title' => $capsule->title]),
                    __('mail.capsule_body', ['title' => $capsule->title, 'url' => $url])
                        ."\n\n".__('mail.salutation'),
                ]);

                Mail::raw($body, function ($message) use ($capsule, $subject) {
                    $message->to($capsule->notify_email)
                        ->subject($subject);
                });

                $capsule->update(['reminder_sent' => true]);
                $sent++;
            } catch (Throwable $e) {
                // Mail config missing or transport failed — degrade gracefully.
                Log::warning('Capsule reminder mail failed', [
                    'capsule_id' => $capsule->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("Capsule reminders: sent {$sent}, failed {$failed}");

        return self::SUCCESS;
    }
}
