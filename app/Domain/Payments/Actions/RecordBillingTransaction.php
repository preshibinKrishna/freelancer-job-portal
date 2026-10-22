<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Enums\BillingOperationType;
use App\Enums\BillingTransactionStatus;
use App\Models\BillingAuditEntry;
use App\Models\BillingInvoice;
use App\Models\BillingSubscription;
use App\Models\BillingTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Persists an intent only; it never contacts a payment provider. */
class RecordBillingTransaction
{
    /** @param array<string, scalar|null> $safeMetadata */
    public function execute(
        ?User $actor,
        BillingOperationType $type,
        string $amount,
        string $currency,
        string $idempotencyKey,
        ?BillingInvoice $invoice = null,
        ?BillingSubscription $subscription = null,
        array $safeMetadata = [],
    ): BillingTransaction {
        if (! preg_match('/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter a positive amount with up to two decimal places.']);
        }
        $currency = strtoupper($currency);
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw ValidationException::withMessages(['currency' => 'Use a three-letter currency code.']);
        }
        if (! preg_match('/^[A-Za-z0-9:_-]{8,114}$/', $idempotencyKey)) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Use a stable idempotency key between 8 and 114 characters.']);
        }
        if (($invoice && $invoice->currency !== $currency) || ($subscription && $subscription->currency !== $currency)) {
            throw ValidationException::withMessages(['currency' => 'Currency must match the related billing record.']);
        }
        foreach (array_keys($safeMetadata) as $key) {
            if (preg_match('/email|phone|address|token|secret|password|payload|document/i', $key)) {
                throw ValidationException::withMessages(['metadata' => 'Sensitive values cannot be added to billing metadata.']);
            }
        }

        return DB::transaction(function () use ($actor, $type, $amount, $currency, $idempotencyKey, $invoice, $subscription, $safeMetadata): BillingTransaction {
            $transaction = BillingTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($transaction) {
                $sameRequest = $transaction->type === $type
                    && self::minorUnits($transaction->amount) === self::minorUnits($amount)
                    && $transaction->currency === $currency
                    && $transaction->billing_invoice_id === $invoice?->id
                    && $transaction->billing_subscription_id === $subscription?->id;
                if (! $sameRequest) {
                    throw ValidationException::withMessages(['idempotencyKey' => 'This idempotency key was already used for a different operation.']);
                }

                return $transaction;
            }

            if ($type === BillingOperationType::Charge && $invoice) {
                $lockedInvoice = BillingInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
                if ($lockedInvoice->status->value !== 'open') {
                    throw ValidationException::withMessages(['invoice' => 'Only open invoices can receive a charge intent.']);
                }
                $reservedCents = $lockedInvoice->transactions()
                    ->where('type', BillingOperationType::Charge)
                    ->whereIn('status', [BillingTransactionStatus::Pending, BillingTransactionStatus::Processing, BillingTransactionStatus::Succeeded])
                    ->get()
                    ->sum(fn (BillingTransaction $existing): int => self::minorUnits($existing->amount));
                if ($reservedCents + self::minorUnits($amount) > self::minorUnits($lockedInvoice->total)) {
                    throw ValidationException::withMessages(['amount' => 'Charge intents cannot exceed the invoice total.']);
                }
            }

            $transaction = BillingTransaction::query()->create([
                'idempotency_key' => $idempotencyKey,
                'billing_invoice_id' => $invoice?->id,
                'billing_subscription_id' => $subscription?->id,
                'type' => $type,
                'status' => BillingTransactionStatus::Pending,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $safeMetadata,
            ]);

            BillingAuditEntry::query()->create([
                'actor_id' => $actor?->id,
                'subject_type' => $transaction->getMorphClass(),
                'subject_id' => $transaction->id,
                'event' => 'transaction.intent_recorded',
                'idempotency_key' => $idempotencyKey.':audit',
                'safe_metadata' => ['type' => $type->value, 'currency' => $currency],
            ]);

            return $transaction;
        });
    }

    private static function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
