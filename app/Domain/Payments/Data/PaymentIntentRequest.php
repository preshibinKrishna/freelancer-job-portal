<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

final readonly class PaymentIntentRequest
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(
        public string $transactionId,
        public string $amount,
        public string $currency,
        public string $idempotencyKey,
        public array $metadata = [],
    ) {}
}
