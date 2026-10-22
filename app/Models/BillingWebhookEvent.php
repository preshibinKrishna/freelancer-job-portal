<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BillingWebhookEvent extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'received_at' => 'immutable_datetime', 'processed_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingWebhookEvent $event) => $event->public_id ??= (string) Str::ulid());
    }
}
