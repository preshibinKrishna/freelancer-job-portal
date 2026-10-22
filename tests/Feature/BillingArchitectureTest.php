<?php

declare(strict_types=1);

use App\Domain\Payments\Actions\AcceptVerifiedWebhook;
use App\Domain\Payments\Actions\RecordBillingTransaction;
use App\Domain\Payments\Actions\RequestBillingRefund;
use App\Domain\Payments\Data\NormalizedWebhookEvent;
use App\Enums\BillingOperationType;
use App\Enums\BillingTransactionStatus;
use App\Models\BillingAuditEntry;
use App\Models\BillingTransaction;
use App\Models\BillingWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('billing transaction intents are idempotent and audited without contacting a provider', function (): void {
    $actor = User::factory()->create();
    $record = app(RecordBillingTransaction::class);
    $first = $record->execute($actor, BillingOperationType::Charge, '125.00', 'USD', 'charge:order-1234');
    $replayed = $record->execute($actor, BillingOperationType::Charge, '125.0', 'USD', 'charge:order-1234');

    expect($first->is($replayed))->toBeTrue()
        ->and(BillingTransaction::query()->count())->toBe(1)
        ->and(BillingAuditEntry::query()->where('event', 'transaction.intent_recorded')->count())->toBe(1)
        ->and($first->status)->toBe(BillingTransactionStatus::Pending);

    expect(fn () => $record->execute($actor, BillingOperationType::Charge, '126.00', 'USD', 'charge:order-1234'))
        ->toThrow(ValidationException::class);
});

test('refund requests are idempotent and cannot exceed a successful charge', function (): void {
    $actor = User::factory()->create();
    $transaction = app(RecordBillingTransaction::class)->execute($actor, BillingOperationType::Charge, '100.00', 'EUR', 'charge:contract-77');
    $transaction->forceFill(['status' => BillingTransactionStatus::Succeeded])->save();
    $refundAction = app(RequestBillingRefund::class);
    $refund = $refundAction->execute($actor, $transaction, '60.00', 'Duplicate service charge', 'refund:contract-77-a');
    $replayed = $refundAction->execute($actor, $transaction, '60.0', 'Duplicate service charge', 'refund:contract-77-a');

    expect($refund->is($replayed))->toBeTrue()
        ->and($transaction->refunds()->count())->toBe(1)
        ->and(BillingAuditEntry::query()->where('event', 'refund.requested')->count())->toBe(1);

    expect(fn () => $refundAction->execute($actor, $transaction, '40.01', 'Additional refund', 'refund:contract-77-b'))
        ->toThrow(ValidationException::class);
});

test('invoice charge intents reserve no more than the invoice total', function (): void {
    $actor = User::factory()->create();
    $invoice = $actor->billingInvoices()->create([
        'number' => 'INV-TEST-001', 'status' => 'open', 'currency' => 'USD',
        'subtotal' => '100.00', 'tax' => '0.00', 'total' => '100.00',
    ]);
    $record = app(RecordBillingTransaction::class);
    $record->execute($actor, BillingOperationType::Charge, '70.00', 'USD', 'charge:invoice-001-a', $invoice);
    $record->execute($actor, BillingOperationType::Charge, '30.00', 'USD', 'charge:invoice-001-b', $invoice);

    expect(fn () => $record->execute($actor, BillingOperationType::Charge, '0.01', 'USD', 'charge:invoice-001-c', $invoice))
        ->toThrow(ValidationException::class);
});

test('verified webhooks are encrypted, deduplicated, and audited once', function (): void {
    $accept = app(AcceptVerifiedWebhook::class);
    $webhook = new NormalizedWebhookEvent('futurepay', 'evt_01', 'charge.succeeded', ['email' => 'private@example.com']);
    $first = $accept->execute($webhook);
    $duplicate = $accept->execute(new NormalizedWebhookEvent('futurepay', 'evt_01', 'charge.succeeded', ['status' => 'changed']));

    expect($first->is($duplicate))->toBeTrue()
        ->and(BillingWebhookEvent::query()->count())->toBe(1)
        ->and($first->payload)->toBe(['email' => 'private@example.com'])
        ->and($first->getRawOriginal('payload'))->not->toContain('private@example.com')
        ->and(BillingAuditEntry::query()->where('event', 'webhook.accepted')->count())->toBe(1);
});

test('payment webhook endpoint stays unavailable until a provider adapter is registered', function (): void {
    $this->postJson(route('webhooks.payments', ['provider' => 'futurepay']), ['event' => 'anything'])
        ->assertServiceUnavailable()
        ->assertJson(['status' => 'unavailable']);
});
