<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Data\NormalizedWebhookEvent;
use App\Domain\Payments\Events\BillingWebhookAccepted;
use App\Models\BillingAuditEntry;
use App\Models\BillingWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Persists an already-authenticated provider event for idempotent downstream processing. */
class AcceptVerifiedWebhook
{
    public function execute(NormalizedWebhookEvent $data): BillingWebhookEvent
    {
        if (! preg_match('/^[a-z0-9_-]{2,60}$/', $data->provider)
            || $data->providerEventId === '' || mb_strlen($data->providerEventId) > 180
            || $data->eventType === '' || mb_strlen($data->eventType) > 120) {
            throw ValidationException::withMessages(['webhook' => 'The verified webhook event has invalid identifiers.']);
        }

        return DB::transaction(function () use ($data): BillingWebhookEvent {
            $event = BillingWebhookEvent::query()->firstOrCreate([
                'provider' => $data->provider,
                'provider_event_id' => $data->providerEventId,
            ], [
                'event_type' => $data->eventType,
                'status' => 'received',
                'payload' => $data->payload,
                'received_at' => now(),
            ]);
            if ($event->wasRecentlyCreated) {
                BillingAuditEntry::query()->create([
                    'subject_type' => $event->getMorphClass(),
                    'subject_id' => $event->id,
                    'event' => 'webhook.accepted',
                    'idempotency_key' => 'webhook:'.Str::limit(hash('sha256', $data->provider.':'.$data->providerEventId), 64, ''),
                    'safe_metadata' => ['provider' => $data->provider, 'event_type' => $data->eventType],
                ]);
                event(new BillingWebhookAccepted($event));
            }

            return $event;
        });
    }
}
