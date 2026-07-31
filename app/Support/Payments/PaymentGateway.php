<?php

namespace App\Support\Payments;

use App\Models\PaymentOrder;

/**
 * One payment provider.
 *
 * Adding a gateway means writing one class against this interface and listing it
 * in config/payments.php — PremiumController does not change. That matters here
 * because ECPay only settles TWD and only accepts mainstream merchants, so this
 * site is expected to move to an adult-friendly, multi-currency provider
 * (CCBill / SegPay / Epoch) once that account exists.
 */
interface PaymentGateway
{
    /** Identifier used in config and logs, e.g. 'ecpay'. */
    public function name(): string;

    /**
     * False when the configured credentials must not be trusted with real money
     * — sandbox mode, or a provider's published test credentials, which make any
     * signature check meaningless because anyone can forge a valid one.
     */
    public function isLive(): bool;

    /** Currency codes this gateway can actually settle, e.g. ['TWD']. */
    public function supportedCurrencies(): array;

    /** Build the form that sends the customer to the hosted checkout page. */
    public function checkout(PaymentOrder $order, string $itemName): CheckoutForm;

    /** Validate a server-to-server callback. Must not mutate any state. */
    public function verifyCallback(array $payload): CallbackResult;

    /** Pull the order number out of the customer-facing return request. */
    public function orderNoFromResult(array $payload): ?string;
}
