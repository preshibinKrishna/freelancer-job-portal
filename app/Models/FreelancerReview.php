<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FreelancerReviewStatus;
use Database\Factories\FreelancerReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property string $public_id @property FreelancerReviewStatus $moderation_status @property int $rating */
class FreelancerReview extends Model
{
    /** @use HasFactory<FreelancerReviewFactory> */
    use HasFactory;

    protected $fillable = ['freelancer_project_id', 'reviewer_id', 'company_id', 'rating', 'title', 'review', 'moderation_status', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'moderation_status' => FreelancerReviewStatus::class, 'moderated_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (FreelancerReview $review): void {
            $review->public_id ??= (string) Str::ulid();
        });
    }

    protected static function newFactory(): FreelancerReviewFactory
    {
        return FreelancerReviewFactory::new();
    }

    /** @return BelongsTo<FreelancerProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(FreelancerProject::class, 'freelancer_project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
