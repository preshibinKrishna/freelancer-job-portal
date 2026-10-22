<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractMilestoneStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property ContractMilestoneStatus $status */
class ContractMilestone extends Model
{
    protected $fillable = ['title', 'description', 'amount', 'duration_days', 'position', 'status', 'submitted_at', 'approved_at', 'released_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'duration_days' => 'integer', 'position' => 'integer', 'status' => ContractMilestoneStatus::class, 'submitted_at' => 'immutable_datetime', 'approved_at' => 'immutable_datetime', 'released_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (ContractMilestone $milestone) => $milestone->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<FreelanceContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(FreelanceContract::class, 'freelance_contract_id');
    }

    /** @return HasMany<ContractMilestoneStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(ContractMilestoneStatusChange::class)->orderBy('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
