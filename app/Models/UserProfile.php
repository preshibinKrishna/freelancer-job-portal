<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocations;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** @property int $user_id @property string $visibility @property string|null $photo_path @property string|null $photo_disk @property string|null $photo_mime_type @property-read User $user */
class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory, HasLocations;

    protected $fillable = [
        'headline', 'bio', 'location', 'location_id', 'profile_slug', 'visibility', 'availability',
        'experience_years', 'notice_period_days', 'expected_salary', 'expected_salary_currency',
        'preferred_work_types', 'linkedin_url', 'github_url', 'photo_disk', 'photo_path', 'photo_mime_type',
    ];

    protected $hidden = ['expected_salary', 'expected_salary_currency', 'photo_disk', 'photo_path', 'photo_mime_type'];

    protected function casts(): array
    {
        return ['preferred_work_types' => 'array', 'expected_salary' => 'decimal:2'];
    }

    protected static function newFactory(): UserProfileFactory
    {
        return UserProfileFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'talent_profile_skills');
    }

    /** @return BelongsToMany<Location, $this> */
    public function preferredLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'talent_profile_preferred_locations');
    }

    /** @return HasMany<ProfileExperience, $this> */
    public function experiences(): HasMany
    {
        return $this->hasMany(ProfileExperience::class);
    }

    /** @return HasMany<ProfileEducation, $this> */
    public function educations(): HasMany
    {
        return $this->hasMany(ProfileEducation::class);
    }

    /** @return HasMany<ProfileCertification, $this> */
    public function certifications(): HasMany
    {
        return $this->hasMany(ProfileCertification::class);
    }

    /** @return HasMany<ProfileLanguage, $this> */
    public function languages(): HasMany
    {
        return $this->hasMany(ProfileLanguage::class);
    }

    /** @return HasMany<ProfilePortfolioLink, $this> */
    public function portfolioLinks(): HasMany
    {
        return $this->hasMany(ProfilePortfolioLink::class);
    }

    /** @return HasMany<ProfileResume, $this> */
    public function resumes(): HasMany
    {
        return $this->hasMany(ProfileResume::class);
    }

    /** @return HasOne<FreelancerProfile, $this> */
    public function freelancerProfile(): HasOne
    {
        return $this->hasOne(FreelancerProfile::class);
    }
}
