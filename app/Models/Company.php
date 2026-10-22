<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanySize;
use App\Enums\CompanyVerificationStatus;
use App\Models\Concerns\HasLocations;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * @property CompanySize|null $company_size
 * @property CompanyVerificationStatus $verification_status
 * @property array<string, string>|null $social_links
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasLocations;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'logo_path', 'cover_image_path', 'description', 'industry', 'website',
        'company_size', 'founded_year', 'headquarters_location_id', 'it_park_id', 'social_links',
        'contact_name', 'contact_email', 'contact_phone', 'benefits', 'technology_stack', 'company_culture',
        'verification_status', 'published_at',
    ];

    protected $hidden = ['owner_id', 'contact_name', 'contact_email', 'contact_phone'];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'company_size' => CompanySize::class,
            'verification_status' => CompanyVerificationStatus::class,
            'founded_year' => 'integer',
            'social_links' => 'array',
            'benefits' => 'array',
            'technology_stack' => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<CompanyMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_memberships')
            ->withPivot(['role', 'status', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    /** @return MorphMany<BillingSubscription, $this> */
    public function billingSubscriptions(): MorphMany
    {
        return $this->morphMany(BillingSubscription::class, 'subscriber');
    }

    /** @return MorphMany<BillingInvoice, $this> */
    public function billingInvoices(): MorphMany
    {
        return $this->morphMany(BillingInvoice::class, 'bill_to');
    }

    /** @return BelongsTo<Location, $this> */
    public function headquarters(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'headquarters_location_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function itPark(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'it_park_id');
    }

    /** @return MorphToMany<Location, $this> */
    public function officeLocations(): MorphToMany
    {
        return $this->locations()->wherePivot('context', 'office');
    }

    /** @return HasMany<CompanyVerification, $this> */
    public function verificationSubmissions(): HasMany
    {
        return $this->hasMany(CompanyVerification::class);
    }

    /** @return HasMany<Job, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /** @return HasMany<EngagementProject, $this> */
    public function engagementProjects(): HasMany
    {
        return $this->hasMany(EngagementProject::class);
    }

    /** @return HasMany<FreelanceContract, $this> */
    public function freelanceContracts(): HasMany
    {
        return $this->hasMany(FreelanceContract::class);
    }

    /** @return HasMany<RecruitmentRequest, $this> */
    public function recruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
