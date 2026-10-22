<?php

declare(strict_types=1);

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Data\NormalizedWebhookEvent;
use App\Domain\Payments\Data\PaymentIntentRequest;
use App\Domain\Payments\Data\PaymentProviderResult;

/**
 * Provider adapters translate between this stable contract and vendor APIs. Implementations
 * must honor the supplied idempotency key and never be called inside a database transaction.
 * No provider is bound or invoked yet.
 */
interface PaymentProvider
{
    public function name(): string;

    public function createPaymentIntent(PaymentIntentRequest $request): PaymentProviderResult;

    public function refund(PaymentIntentRequest $request): PaymentProviderResult;

    /** Verify authenticity before returning a normalized event. Invalid signatures must throw. */
    /** @param array<string, string> $headers */
    public function verifyAndNormalizeWebhook(array $headers, string $rawBody): NormalizedWebhookEvent;
}
