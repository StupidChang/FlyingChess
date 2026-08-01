<?php

namespace Tests\Feature;

use App\Models\EmailSuppression;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 退信／客訴回饋。
 *
 * 這個端點是公開的,所以最重要的一條測試不是「有效通知會被記下來」,而是
 * **偽造的通知會被擋掉** —— 少了驗簽,任何人都能把別人的信箱加進抑制清單,
 * 那等於送對方一個「讓某人再也收不到本站信件」的功能。
 */
class EmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

    private const CERT_URL = 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-test.pem';

    /** 自己簽一則 SNS 通知,並讓憑證網址回傳對應的公鑰憑證。 */
    private function signedPayload(array $extra = [], string $type = 'Notification'): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $csr = openssl_csr_new(['commonName' => 'sns.amazonaws.com'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 1);
        openssl_x509_export($cert, $certPem);

        $payload = array_merge([
            'Type' => $type,
            'MessageId' => 'msg-1',
            'TopicArn' => 'arn:aws:sns:us-east-1:1:ses-feedback',
            'Timestamp' => now()->toIso8601String(),
            'SignatureVersion' => '1',
            'SigningCertURL' => self::CERT_URL,
        ], $extra);

        $fields = $type === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $canonical = '';
        foreach ($fields as $field) {
            if (isset($payload[$field])) {
                $canonical .= $field."\n".$payload[$field]."\n";
            }
        }

        openssl_sign($canonical, $signature, $key, OPENSSL_ALGO_SHA1);
        $payload['Signature'] = base64_encode($signature);

        Http::fake([self::CERT_URL => Http::response($certPem)]);

        return $payload;
    }

    public function test_a_permanent_bounce_suppresses_the_address(): void
    {
        $payload = $this->signedPayload(['Message' => json_encode([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bounceSubType' => 'General',
                'bouncedRecipients' => [['emailAddress' => 'Gone@Example.com']],
            ],
        ])]);

        $this->postJson('/ses/feedback', $payload)->assertOk();

        // 一律小寫存:SES 回報的大小寫不保證跟使用者輸入的一致。
        $this->assertTrue(EmailSuppression::isSuppressed('gone@example.com'));
        $this->assertSame('bounce', EmailSuppression::first()->reason);
    }

    public function test_a_transient_bounce_is_ignored(): void
    {
        $payload = $this->signedPayload(['Message' => json_encode([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Transient',
                'bouncedRecipients' => [['emailAddress' => 'full@example.com']],
            ],
        ])]);

        $this->postJson('/ses/feedback', $payload)->assertOk();

        // 信箱滿了是暫時的。永久封鎖等於讓使用者再也收不到信。
        $this->assertSame(0, EmailSuppression::count());
    }

    public function test_a_complaint_suppresses_the_address(): void
    {
        $payload = $this->signedPayload(['Message' => json_encode([
            'notificationType' => 'Complaint',
            'complaint' => [
                'complaintFeedbackType' => 'abuse',
                'complainedRecipients' => [['emailAddress' => 'angry@example.com']],
            ],
        ])]);

        $this->postJson('/ses/feedback', $payload)->assertOk();

        $this->assertSame('complaint', EmailSuppression::first()->reason);
    }

    public function test_an_unsigned_notification_is_rejected(): void
    {
        $this->postJson('/ses/feedback', [
            'Type' => 'Notification',
            'MessageId' => 'forged',
            'Message' => json_encode([
                'notificationType' => 'Bounce',
                'bounce' => [
                    'bounceType' => 'Permanent',
                    'bouncedRecipients' => [['emailAddress' => 'victim@example.com']],
                ],
            ]),
        ])->assertForbidden();

        $this->assertSame(0, EmailSuppression::count());
    }

    public function test_a_signing_cert_url_outside_aws_is_rejected(): void
    {
        // 憑證網址是訊息自己帶的欄位。不檢查主機的話,攻擊者填自己的網址、
        // 用自己的私鑰簽,一樣「驗得過」。
        $payload = $this->signedPayload(['Message' => json_encode(['notificationType' => 'Bounce'])]);
        $payload['SigningCertURL'] = 'https://attacker.example.com/cert.pem';

        $this->postJson('/ses/feedback', $payload)->assertForbidden();
    }

    public function test_suppressed_addresses_never_receive_mail(): void
    {
        EmailSuppression::suppress('blocked@example.com', 'bounce');

        Mail::raw('hello', fn ($m) => $m->to('blocked@example.com')->subject('test'));
        Mail::raw('hello', fn ($m) => $m->to('fine@example.com')->subject('test'));

        /* 攔在 MessageSending —— 所有寄信的唯一出口。真的要驗的是「有沒有交給
           transport」,不是「地址在不在清單裡」:後者只證明資料寫對了,不能證明
           那一封真的沒送出去。array transport 會把送出的信留在 messages 裡。 */
        $sent = collect(Mail::mailer()->getSymfonyTransport()->messages())
            ->flatMap(fn ($m) => collect($m->getOriginalMessage()->getTo())
                ->map(fn ($a) => $a->getAddress()));

        $this->assertNotContains('blocked@example.com', $sent);
        $this->assertContains('fine@example.com', $sent);
    }

    public function test_registration_is_rate_limited_per_hour(): void
    {
        // 每次註冊都會寄一封驗證信,所以註冊速率就是寄信速率。
        for ($i = 0; $i < 9; $i++) {
            $this->withUnencryptedCookie('age_verified', '1')->post('/tw/register', [
                'name' => 'user'.$i,
                'email' => "user{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
            $this->travel(70)->seconds();   // 避開 60 秒的連點限制
        }

        $this->assertLessThanOrEqual(8, User::count());
    }
}
