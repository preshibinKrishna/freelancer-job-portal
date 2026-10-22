<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyVerificationStatus;
use Database\Factories\CompanyVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property CompanyVerificationStatus $status */
class CompanyVerification extends Model
{
    /** @use HasFactory<CompanyVerificationFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'submitted_by', 'status', 'submission_note',
        'reviewed_by', 'reviewer_notes', 'reviewed_at',
    ];

    protected $hidden = ['reviewer_notes'];

    protected static function booted(): void
    {
        static::creating(function (CompanyVerification $verification): void {
            $verification->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['status' => CompanyVerificationStatus::class, 'reviewed_at' => 'immutable_datetime'];
    }

    protected static function newFactory(): CompanyVerificationFactory
    {
        return CompanyVerificationFactory::new();
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
