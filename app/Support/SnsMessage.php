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

        return Http::timeout(10)->get($url)->successful();
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
            $res = Http::timeout(10)->get($url);

            return $res->successful() ? $res->body() : null;
        } catch (\Throwable $e) {
            Log::warning('SNS: could not fetch signing cert', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
