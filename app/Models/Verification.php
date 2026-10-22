<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property VerificationStatus $status
 * @property VerificationType $type
 * @property CarbonImmutable|null $expires_at
 * @property int $user_id
 * @property string $public_id
 */
#[Fillable(['user_id', 'type', 'label', 'evidence', 'status', 'reviewed_by', 'reviewer_notes', 'reviewed_at', 'expires_at'])]
class Verification extends Model
{
    protected function casts(): array
    {
        return ['type' => VerificationType::class, 'status' => VerificationStatus::class, 'reviewed_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Verification $verification) => $verification->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isCurrentlyApproved(): bool
    {
        return $this->status === VerificationStatus::Approved && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function displayStatus(): VerificationStatus
    {
        return $this->status === VerificationStatus::Approved && $this->expires_at?->isPast()
            ? VerificationStatus::Expired
            : $this->status;
    }
}
