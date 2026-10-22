<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FreelancerVerificationStatus;
use Database\Factories\FreelancerProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property FreelancerVerificationStatus $verification_status
 */
class FreelancerProfile extends Model
{
    /** @use HasFactory<FreelancerProfileFactory> */
    use HasFactory;

    protected $fillable = ['user_profile_id', 'hourly_rate', 'project_rate', 'currency', 'weekly_capacity', 'is_listed', 'verification_status', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['hourly_rate' => 'decimal:2', 'project_rate' => 'decimal:2', 'weekly_capacity' => 'integer', 'is_listed' => 'boolean', 'verification_status' => FreelancerVerificationStatus::class, 'verified_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (FreelancerProfile $profile): void {
            $profile->public_id ??= (string) Str::ulid();
        });
    }

    protected static function newFactory(): FreelancerProfileFactory
    {
        return FreelancerProfileFactory::new();
    }

    /** @return BelongsTo<UserProfile, $this> */
    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }

    /** @return HasMany<FreelancerProject, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(FreelancerProject::class);
    }

    /** @return HasManyThrough<FreelancerReview, FreelancerProject, $this> */
    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(FreelancerReview::class, FreelancerProject::class, 'freelancer_profile_id', 'freelancer_project_id');
    }

    /** @return BelongsToMany<ProjectCategory, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCategory::class, 'freelancer_profile_categories');
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('is_listed', true)
            ->whereHas('talentProfile', fn (Builder $profile): Builder => $profile->where('visibility', 'public')->whereHas('user', fn (Builder $user): Builder => $user->where('status', 'active')->whereNotNull('email_verified_at')));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
