<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property JobApplicationStatus $status */
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected $fillable = ['job_id', 'candidate_id', 'cover_letter', 'status', 'submitted_at', 'withdrawn_at'];

    protected static function booted(): void
    {
        static::creating(function (JobApplication $application): void {
            $application->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['status' => JobApplicationStatus::class, 'submitted_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime'];
    }

    protected static function newFactory(): JobApplicationFactory
    {
        return JobApplicationFactory::new();
    }

    /** @return BelongsTo<Job, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /** @return HasMany<JobApplicationStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(JobApplicationStatusChange::class)->oldest('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
