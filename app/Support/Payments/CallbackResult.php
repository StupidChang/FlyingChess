<?php

namespace App\Support\Payments;

/**
 * The outcome of validating a gateway's server-to-server callback.
 *
 * A driver only decides "is this callback genuine, which order is it for, and
 * did it succeed" — applying it to the order and the user's membership stays in
 * the controller, so that logic is shared by every gateway.
 *
 * `response` is what must be echoed back: gateways check it to decide whether to
 * retry, and the exact string is provider-specific (ECPay wants `1|OK`).
 */
final class CallbackResult
{
    private function __construct(
        public readonly bool $valid,
        public readonly bool $paid,
        public readonly ?string $orderNo,
        public readonly ?string $tradeNo,
        public readonly string $response,
        /** Amount the gateway says was charged, in the currency's minor unit. */
        public readonly ?int $amountMinor = null,
    ) {}

    /**
     * Genuine callback reporting a successful payment. $amountMinor must already
     * be converted to the minor unit — the driver knows how its provider reports
     * amounts, the controller only compares it against the stored order.
     */
    public static function paid(string $orderNo, ?string $tradeNo, string $response, int $amountMinor): self
    {
        return new self(true, true, $orderNo, $tradeNo, $response, $amountMinor);
    }

    /** Genuine callback, but the payment did not go through. */
    public static function failed(string $orderNo, string $response): self
    {
        return new self(true, false, $orderNo, null, $response);
    }

    /** Not trustworthy — bad signature, wrong merchant, unknown order. */
    public static function rejected(string $response): self
    {
        return new self(false, false, null, null, $response);
    }
}
