<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class BillingSubscription extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['status' => BillingSubscriptionStatus::class, 'amount' => 'decimal:2', 'starts_at' => 'immutable_datetime', 'current_period_ends_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingSubscription $subscription) => $subscription->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<BillingPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'billing_plan_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<BillingInvoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    /** @return HasMany<BillingTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
