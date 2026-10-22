<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use App\Models\User;

class FreelancerReviewPolicy
{
    public function create(User $actor, FreelancerProject $project): bool
    {
        $company = $project->requestedCompany;
        $ownerId = $project->freelancerProfile?->talentProfile?->user_id;
        if (! $actor->isActive() || ! $actor->hasVerifiedEmail() || $actor->id === $ownerId || $company === null || $project->completed_at === null || $project->verification_status->value !== 'verified' || $project->review()->exists()) {
            return false;
        }

        return $company->owner_id === $actor->id
            || $company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists();
    }

    public function view(?User $actor, FreelancerReview $review): bool
    {
        return $review->moderation_status->value === 'approved'
            || ($actor !== null && ($review->reviewer_id === $actor->id || $actor->hasPermission('freelancer.review.moderate')));
    }

    public function moderate(User $actor): bool
    {
        return $actor->isActive() && $actor->hasPermission('freelancer.review.moderate');
    }
}
