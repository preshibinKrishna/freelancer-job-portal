<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Enums\WorkplaceType;
use Carbon\CarbonImmutable;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property EmploymentType $employment_type
 * @property WorkplaceType $workplace_type
 * @property JobStatus $status
 * @property JobVisibility $visibility
 * @property string $slug
 * @property int $created_by
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $application_deadline
 * @property array<string>|null $responsibilities
 * @property array<string>|null $requirements
 * @property array<string>|null $benefits
 */
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'company_id', 'created_by', 'title', 'slug', 'description', 'employment_type', 'workplace_type',
        'experience_min', 'experience_max', 'salary_min', 'salary_max', 'currency', 'location_id', 'it_park_id',
        'responsibilities', 'requirements', 'benefits', 'application_deadline', 'visibility', 'status',
        'published_at', 'closed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            $job->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'workplace_type' => WorkplaceType::class,
            'status' => JobStatus::class,
            'visibility' => JobVisibility::class,
            'responsibilities' => 'array',
            'requirements' => 'array',
            'benefits' => 'array',
            'application_deadline' => 'immutable_date',
            'published_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): JobFactory
    {
        return JobFactory::new();
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

    /** @return BelongsTo<Location, $this> */
    public function itPark(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'it_park_id');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill');
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_jobs')->withTimestamps();
    }

    /** @param Builder<Job> $query
     * @return Builder<Job>
     */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Published)
            ->where('visibility', JobVisibility::Public)
            ->where(fn (Builder $builder) => $builder->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', today()))
            ->whereHas('company', fn (Builder $company) => $company->whereNotNull('published_at'));
    }

    public function acceptsApplications(): bool
    {
        if ($this->status !== JobStatus::Published
            || ($this->application_deadline !== null && ! $this->application_deadline->isToday() && ! $this->application_deadline->isFuture())) {
            return false;
        }

        return $this->relationLoaded('company')
            ? $this->company?->isPublished() === true
            : $this->company()->whereNotNull('published_at')->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
