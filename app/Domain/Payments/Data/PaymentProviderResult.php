<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

use App\Enums\BillingTransactionStatus;

final readonly class PaymentProviderResult
{
    public function __construct(
        public string $providerReference,
        public BillingTransactionStatus $status,
        public ?string $failureCode = null,
    ) {}
}
