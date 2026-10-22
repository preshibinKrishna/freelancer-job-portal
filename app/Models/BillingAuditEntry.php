<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class BillingAuditEntry extends Model
{
    public $timestamps = false;

    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['safe_metadata' => 'array', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingAuditEntry $entry) => $entry->public_id ??= (string) Str::ulid());
        static::updating(static function (): never {
            throw new LogicException('Billing audit entries are append-only.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Billing audit entries are append-only.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
