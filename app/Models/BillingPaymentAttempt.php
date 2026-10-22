<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingPaymentAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BillingPaymentAttempt extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['attempt_number' => 'integer', 'status' => BillingPaymentAttemptStatus::class, 'metadata' => 'array', 'attempted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingPaymentAttempt $attempt) => $attempt->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<BillingTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BillingTransaction::class, 'billing_transaction_id');
    }
}
