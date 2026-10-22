<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingOperationType;
use App\Enums\BillingTransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property BillingOperationType $type
 * @property BillingTransactionStatus $status
 * @property string $amount
 */
class BillingTransaction extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['type' => BillingOperationType::class, 'status' => BillingTransactionStatus::class, 'amount' => 'decimal:2', 'metadata' => 'array', 'succeeded_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingTransaction $transaction) => $transaction->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<BillingInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    /** @return BelongsTo<BillingSubscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BillingSubscription::class, 'billing_subscription_id');
    }

    /** @return HasMany<BillingRefund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(BillingRefund::class);
    }

    /** @return HasMany<BillingPaymentAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(BillingPaymentAttempt::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
