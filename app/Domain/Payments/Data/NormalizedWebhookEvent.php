<?php

declare(strict_types=1);

namespace App\Domain\Payments\Data;

final readonly class NormalizedWebhookEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $provider,
        public string $providerEventId,
        public string $eventType,
        public array $payload,
    ) {}
}
