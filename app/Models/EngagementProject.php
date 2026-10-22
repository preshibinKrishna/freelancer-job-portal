<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EngagementProjectStatus;
use App\Enums\ProjectPricingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property EngagementProjectStatus $status
 * @property ProjectPricingType $pricing_type
 * @property Carbon|null $application_deadline
 */
class EngagementProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'created_by', 'title', 'description', 'budget_min', 'budget_max', 'currency',
        'pricing_type', 'estimated_duration_days', 'experience_min', 'location_id', 'is_remote',
        'application_deadline', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'budget_min' => 'decimal:2', 'budget_max' => 'decimal:2', 'pricing_type' => ProjectPricingType::class,
            'estimated_duration_days' => 'integer', 'experience_min' => 'integer', 'is_remote' => 'boolean',
            'application_deadline' => 'immutable_datetime', 'status' => EngagementProjectStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (EngagementProject $project) => $project->public_id ??= (string) Str::ulid());
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

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'engagement_project_skills');
    }

    /** @return HasMany<EngagementProjectAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(EngagementProjectAttachment::class);
    }

    /** @return HasMany<ProjectProposal, $this> */
    public function proposals(): HasMany
    {
        return $this->hasMany(ProjectProposal::class);
    }

    /** @return HasMany<EngagementProjectMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(EngagementProjectMilestone::class);
    }

    /** @return HasOne<FreelanceContract, $this> */
    public function contract(): HasOne
    {
        return $this->hasOne(FreelanceContract::class, 'engagement_project_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_engagement_projects')->withTimestamps();
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->whereIn('status', [EngagementProjectStatus::Published, EngagementProjectStatus::Reviewing])
            ->where(fn (Builder $builder) => $builder->whereNull('application_deadline')->orWhere('application_deadline', '>=', now()))
            ->whereHas('company', fn (Builder $company) => $company->whereNotNull('published_at'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
