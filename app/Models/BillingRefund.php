<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingRefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property string $amount */
class BillingRefund extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['status' => BillingRefundStatus::class, 'amount' => 'decimal:2', 'metadata' => 'array', 'refunded_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingRefund $refund) => $refund->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<BillingTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BillingTransaction::class, 'billing_transaction_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
