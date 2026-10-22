<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingPlanCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BillingPlan extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['category' => BillingPlanCategory::class, 'amount' => 'decimal:2', 'interval_count' => 'integer', 'features' => 'array', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BillingPlan $plan) => $plan->public_id ??= (string) Str::ulid());
    }

    /** @return HasMany<BillingSubscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BillingSubscription::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
