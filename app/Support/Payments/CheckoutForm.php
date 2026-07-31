<?php

namespace App\Support\Payments;

/**
 * What a gateway needs the browser to submit to start a payment.
 *
 * Every gateway in the adult space (CCBill, SegPay, Epoch) works the same way as
 * ECPay here — an auto-submitting form to a hosted checkout page — so this shape
 * covers them without change. A gateway that instead returns a redirect URL can
 * express it as an empty params list plus its action URL.
 */
final class CheckoutForm
{
    public function __construct(
        public readonly string $actionUrl,
        public readonly array $params,
        public readonly string $method = 'POST',
    ) {}
}
