<?php

namespace App\Http\Controllers;

use App\Models\EmailSuppression;
use App\Support\SnsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 接 SES 經由 SNS 送來的退信與客訴通知。
 *
 * 目的不是「擋住寄信」—— SES 自己的 suppression list 已經在做那件事了。目的是
 * **看得見**:哪些地址退信、退了幾封、有沒有人按垃圾信。寄信被停權之前,退信率
 * 上升通常是唯一的預警,而那個數字要自己留才查得到。
 *
 * 記下來的地址也會反過來擋住站上的寄信(見 StopMailToSuppressedAddress),
 * 免得同一個壞地址一直被重試,把退信率愈推愈高。
 */
class SesFeedbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response('bad payload', 400);
        }

        $message = new SnsMessage($payload);

        // 驗簽失敗一律 403。這個端點是公開的,不驗的話任何人都能偽造一則
        // 「某地址退信了」,等於送別人一個封鎖任意信箱的功能。
        if (! $message->isValid()) {
            Log::warning('SES feedback: invalid SNS signature', ['type' => $message->type()]);

            return response('invalid signature', 403);
        }

        if ($message->type() === 'SubscriptionConfirmation') {
            $message->confirmSubscription();

            return response('subscription confirmed');
        }

        if ($message->type() !== 'Notification') {
            return response('ignored');
        }

        $this->record($message->decodedMessage());

        return response('ok');
    }

    private function record(array $body): void
    {
        $type = $body['notificationType'] ?? $body['eventType'] ?? null;

        if ($type === 'Bounce') {
            $bounce = $body['bounce'] ?? [];

            // 只擋永久退信。Transient 是暫時性的(對方信箱滿了之類),
            // 把它加進黑名單會讓使用者永遠收不到信。
            if (($bounce['bounceType'] ?? '') !== 'Permanent') {
                return;
            }

            foreach ($bounce['bouncedRecipients'] ?? [] as $r) {
                if (! empty($r['emailAddress'])) {
                    EmailSuppression::suppress(
                        $r['emailAddress'],
                        'bounce',
                        $bounce['bounceSubType'] ?? null,
                    );
                }
            }

            return;
        }

        if ($type === 'Complaint') {
            $complaint = $body['complaint'] ?? [];

            foreach ($complaint['complainedRecipients'] ?? [] as $r) {
                if (! empty($r['emailAddress'])) {
                    EmailSuppression::suppress(
                        $r['emailAddress'],
                        'complaint',
                        $complaint['complaintFeedbackType'] ?? null,
                    );
                }
            }
        }
    }
}
