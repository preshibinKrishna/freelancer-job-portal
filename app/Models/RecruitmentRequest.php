<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentUrgency;
use App\Enums\WorkplaceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property RecruitmentRequestStatus $status
 * @property WorkplaceType $workplace_type
 * @property RecruitmentUrgency $urgency
 */
class RecruitmentRequest extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'created_by', 'assigned_recruiter_id', 'position', 'number_of_openings', 'experience_min', 'experience_max', 'salary_min', 'salary_max', 'currency', 'location_id', 'workplace_type', 'notice_period_days', 'urgency', 'description', 'status', 'submitted_at'];

    protected function casts(): array
    {
        return ['number_of_openings' => 'integer', 'experience_min' => 'integer', 'experience_max' => 'integer', 'salary_min' => 'decimal:2', 'salary_max' => 'decimal:2', 'notice_period_days' => 'integer', 'workplace_type' => WorkplaceType::class, 'urgency' => RecruitmentUrgency::class, 'status' => RecruitmentRequestStatus::class, 'submitted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (RecruitmentRequest $request) => $request->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'recruitment_request_skills');
    }

    /** @return HasMany<RecruitmentPipelineEntry, $this> */
    public function pipelineEntries(): HasMany
    {
        return $this->hasMany(RecruitmentPipelineEntry::class);
    }

    /** @return HasMany<RecruitmentActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
