<?php

declare(strict_types=1);

namespace App\Domain\Payments\Events;

use App\Models\BillingWebhookEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class BillingWebhookAccepted implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly BillingWebhookEvent $webhookEvent) {}
}
