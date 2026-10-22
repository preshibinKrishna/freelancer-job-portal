<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FreelancerProjectVerificationStatus;
use Database\Factories\FreelancerProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/** @property string $public_id @property FreelancerProjectVerificationStatus $verification_status */
class FreelancerProject extends Model
{
    /** @use HasFactory<FreelancerProjectFactory> */
    use HasFactory;

    protected $fillable = ['project_category_id', 'title', 'summary', 'role', 'project_url', 'completed_at', 'requested_company_id', 'verification_status', 'verified_by', 'verified_at', 'is_visible', 'is_featured'];

    protected function casts(): array
    {
        return ['completed_at' => 'date', 'verified_at' => 'immutable_datetime', 'verification_status' => FreelancerProjectVerificationStatus::class, 'is_visible' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (FreelancerProject $project): void {
            $project->public_id ??= (string) Str::ulid();
        });
    }

    protected static function newFactory(): FreelancerProjectFactory
    {
        return FreelancerProjectFactory::new();
    }

    /** @return BelongsTo<FreelancerProfile, $this> */
    public function freelancerProfile(): BelongsTo
    {
        return $this->belongsTo(FreelancerProfile::class);
    }

    /** @return BelongsTo<ProjectCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function requestedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'requested_company_id');
    }

    /** @return HasOne<FreelancerReview, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(FreelancerReview::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
