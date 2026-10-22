<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Enums\FreelancerReviewStatus;
use App\Models\AuditLog;
use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SubmitFreelancerReview
{
    public function execute(FreelancerProject $project, User $reviewer, int $rating, ?string $title, string $reviewText): FreelancerReview
    {
        Gate::forUser($reviewer)->authorize('create', [FreelancerReview::class, $project]);

        return DB::transaction(function () use ($project, $rating, $reviewText, $reviewer, $title): FreelancerReview {
            $lockedProject = FreelancerProject::query()->lockForUpdate()->findOrFail($project->id);
            Gate::forUser($reviewer)->authorize('create', [FreelancerReview::class, $lockedProject]);
            $review = $lockedProject->review()->create([
                'reviewer_id' => $reviewer->id,
                'company_id' => $lockedProject->requested_company_id,
                'rating' => $rating,
                'title' => $title,
                'review' => $reviewText,
                'moderation_status' => FreelancerReviewStatus::Pending,
            ]);
            AuditLog::query()->create(['actor_id' => $reviewer->id, 'subject_id' => $lockedProject->freelancerProfile?->talentProfile?->user_id, 'event' => 'freelancer.review_submitted', 'metadata' => ['review_public_id' => $review->public_id]]);

            return $review;
        });
    }
}
