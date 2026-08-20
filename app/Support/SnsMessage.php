<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 驗證一則 Amazon SNS 通知確實來自 AWS。
 *
 * 這個端點是**公開的**:任何人都可以往上面 POST。沒有驗簽的話,別人就能偽造
 * 一則「某某地址退信了」把任意使用者加進抑制清單,等於一個免費的封鎖別人信箱
 * 的功能。所以簽章不是可選項。
 *
 * 驗法照 AWS 的規範:把指定欄位依固定順序串成 canonical string,用訊息附的
 * 憑證公鑰去驗 Signature。
 *
 * ⚠ 憑證網址一定要驗:那是訊息裡自己帶的欄位,不檢查的話攻擊者填自己的網址、
 * 用自己的私鑰簽,一樣「驗得過」。只接受 https 且主機是 sns.<region>.amazonaws.com。
 */
class SnsMessage
{
    /** canonical string 的欄位順序,由 AWS 規定,不能改。 */
    private const FIELDS = [
        'Notification' => ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'],
        'SubscriptionConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
        'UnsubscribeConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
    ];

    public function __construct(private readonly array $payload) {}

    public function type(): string
    {
        return (string) ($this->payload['Type'] ?? '');
    }

    public function get(string $key): ?string
    {
        return isset($this->payload[$key]) ? (string) $this->payload[$key] : null;
    }

    /** 訊息本體。SES 把實際的退信資料放在這裡,是一段 JSON 字串。 */
    public function decodedMessage(): array
    {
        return json_decode((string) ($this->payload['Message'] ?? ''), true) ?: [];
    }

    public function isValid(): bool
    {
        $fields = self::FIELDS[$this->type()] ?? null;
        if (! $fields || empty($this->payload['Signature'])) {
            return false;
        }

        $certUrl = (string) ($this->payload['SigningCertURL'] ?? $this->payload['SigningCertUrl'] ?? '');
        if (! $this->certUrlLooksLikeAws($certUrl)) {
            Log::warning('SNS: rejected signing cert URL', ['url' => $certUrl]);

            return false;
        }

        // Topic 白名單。驗簽只證明「這訊息真的來自某個 AWS 帳號」,不證明是**我們**
        // 的 Topic —— 任何人都能在自己的 AWS 帳號建一個 Topic、把我們的端點訂進去,
        // 發出來的通知一樣簽章合法。不綁 Topic 的話,對方就能把任意信箱塞進抑制清單。
        //
        // 未設定 topic_arn 時 fail-closed(拒收),但把這則訊息的真實 TopicArn 記進
        // log —— 這樣第一次收到合法通知時,把那串 ARN 貼進 SNS_TOPIC_ARN 即可啟用,
        // 不必事先知道 ARN 就能安全上線。
        $expectedTopic = (string) config('services.ses.topic_arn', '');
        $actualTopic = (string) ($this->payload['TopicArn'] ?? '');
        if ($expectedTopic === '') {
            Log::warning('SNS: SNS_TOPIC_ARN 未設定,通知一律拒收。把下面這個 ARN 填進 .env 的 SNS_TOPIC_ARN 即可啟用。', ['TopicArn' => $actualTopic]);

            return false;
        }
        if (! hash_equals($expectedTopic, $actualTopic)) {
            Log::warning('SNS: rejected unexpected TopicArn', ['TopicArn' => $actualTopic]);

            return false;
        }

        $canonical = '';
        foreach ($fields as $field) {
            if (! isset($this->payload[$field])) {
                continue;   // Subject 之類的欄位可能不存在,不存在就整個跳過
            }
            $canonical .= $field."\n".$this->payload[$field]."\n";
        }

        $cert = $this->fetchCert($certUrl);
        if (! $cert) {
            return false;
        }

        $key = openssl_get_publickey($cert);
        if (! $key) {
            return false;
        }

        // SignatureVersion 1 用 SHA1,2 用 SHA256。沒寫的話當作 1(AWS 的預設)。
        $algo = ((string) ($this->payload['SignatureVersion'] ?? '1')) === '2'
            ? OPENSSL_ALGO_SHA256
            : OPENSSL_ALGO_SHA1;

        return openssl_verify($canonical, base64_decode((string) $this->payload['Signature']), $key, $algo) === 1;
    }

    /**
     * 訂閱確認:AWS 會先送一則帶 SubscribeURL 的訊息,要我們自己去打它,
     * 才算真的訂閱成功。驗過簽之後才打,不然等於幫任何人發 HTTP 請求。
     */
    public function confirmSubscription(): bool
    {
        $url = $this->get('SubscribeURL');
        if (! $url || ! $this->certUrlLooksLikeAws($url)) {
            return false;
        }

        // 不跟隨轉址:主機白名單只驗第一跳,若 AWS 網址回一個 3xx 指到別處,
        // 跟隨的話就等於被帶去打任意主機(SSRF)。SubscribeURL 是終端網址,本來
        // 就不該有轉址。fetchCert 同理。
        return Http::withoutRedirecting()->timeout(10)->get($url)->successful();
    }

    private function certUrlLooksLikeAws(string $url): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https'
            && (bool) preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $parts['host'] ?? '');
    }

    private function fetchCert(string $url): ?string
    {
        try {
            $res = Http::withoutRedirecting()->timeout(10)->get($url);

            return $res->successful() ? $res->body() : null;
        } catch (\Throwable $e) {
            Log::warning('SNS: could not fetch signing cert', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
