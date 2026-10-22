<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\BillingAuditEntry;
use App\Models\BillingRefund;
use App\Models\BillingTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Records a refund request. It does not call a provider or move money. */
class RequestBillingRefund
{
    public function execute(User $actor, BillingTransaction $transaction, string $amount, string $reason, string $idempotencyKey): BillingRefund
    {
        if (! preg_match('/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/', $amount) || (float) $amount <= 0 || trim($reason) === '') {
            throw ValidationException::withMessages(['refund' => 'Enter a positive refund amount and a reason.']);
        }
        if (! preg_match('/^[A-Za-z0-9:_-]{8,114}$/', $idempotencyKey)) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Use a stable idempotency key between 8 and 114 characters.']);
        }

        return DB::transaction(function () use ($actor, $transaction, $amount, $reason, $idempotencyKey): BillingRefund {
            $locked = BillingTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status->value !== 'succeeded' || $locked->type->value !== 'charge') {
                throw ValidationException::withMessages(['transaction' => 'Only successful charges can be refunded.']);
            }
            $refund = BillingRefund::query()->firstOrCreate(['idempotency_key' => $idempotencyKey], [
                'billing_transaction_id' => $locked->id,
                'status' => 'pending',
                'currency' => $locked->currency,
                'amount' => $amount,
                'reason' => trim($reason),
                'requested_by' => $actor->id,
            ]);
            if (! $refund->wasRecentlyCreated) {
                if ($refund->billing_transaction_id !== $locked->id || self::minorUnits($refund->amount) !== self::minorUnits($amount) || $refund->reason !== trim($reason)) {
                    throw ValidationException::withMessages(['idempotencyKey' => 'This idempotency key was already used for a different refund.']);
                }

                return $refund;
            }

            $reserved = self::minorUnits((string) $locked->refunds()->whereNotIn('status', ['failed', 'cancelled'])->sum('amount'));
            if ($reserved > self::minorUnits($locked->amount)) {
                throw ValidationException::withMessages(['amount' => 'Refund requests exceed the original charge.']);
            }
            BillingAuditEntry::query()->create([
                'actor_id' => $actor->id,
                'subject_type' => $refund->getMorphClass(),
                'subject_id' => $refund->id,
                'event' => 'refund.requested',
                'idempotency_key' => $idempotencyKey.':audit',
                'safe_metadata' => ['currency' => $refund->currency],
            ]);

            return $refund;
        });
    }

    private static function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
