<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property BillingInvoiceStatus $status
 * @property string $total
 */
class BillingInvoice extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['status' => BillingInvoiceStatus::class, 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'issued_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'paid_at' => 'immutable_datetime', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingInvoice $invoice) => $invoice->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<BillingSubscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BillingSubscription::class, 'billing_subscription_id');
    }

    /** @return MorphTo<Model, $this> */
    public function billTo(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<BillingInvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class, 'billing_invoice_id');
    }

    /** @return HasMany<BillingTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'billing_invoice_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
