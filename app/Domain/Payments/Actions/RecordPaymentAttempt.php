<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\BillingAuditEntry;
use App\Models\BillingPaymentAttempt;
use App\Models\BillingTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Reserves an attempt record; it does not send a request to a provider. */
class RecordPaymentAttempt
{
    public function execute(BillingTransaction $transaction, string $idempotencyKey, ?User $actor = null): BillingPaymentAttempt
    {
        if (! preg_match('/^[A-Za-z0-9:_-]{8,114}$/', $idempotencyKey)) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Use a stable idempotency key between 8 and 114 characters.']);
        }

        return DB::transaction(function () use ($transaction, $idempotencyKey, $actor): BillingPaymentAttempt {
            $locked = BillingTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if (in_array($locked->status->value, ['succeeded', 'reversed'], true)) {
                throw ValidationException::withMessages(['transaction' => 'A completed transaction cannot receive another payment attempt.']);
            }
            $existing = BillingPaymentAttempt::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->billing_transaction_id !== $locked->id) {
                    throw ValidationException::withMessages(['idempotencyKey' => 'This key belongs to a different payment attempt.']);
                }

                return $existing;
            }
            $attempt = $locked->attempts()->create([
                'attempt_number' => ((int) $locked->attempts()->max('attempt_number')) + 1,
                'status' => 'created',
                'idempotency_key' => $idempotencyKey,
            ]);
            BillingAuditEntry::query()->create([
                'actor_id' => $actor?->id,
                'subject_type' => $attempt->getMorphClass(),
                'subject_id' => $attempt->id,
                'event' => 'payment_attempt.recorded',
                'idempotency_key' => $idempotencyKey.':audit',
                'safe_metadata' => ['attempt_number' => $attempt->attempt_number],
            ]);

            return $attempt;
        });
    }
}
