<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Enums\FreelancerReviewStatus;
use App\Models\AuditLog;
use App\Models\FreelancerReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ModerateFreelancerReview
{
    public function execute(FreelancerReview $review, User $moderator, FreelancerReviewStatus $status): FreelancerReview
    {
        Gate::forUser($moderator)->authorize('moderate', FreelancerReview::class);
        if ($status === FreelancerReviewStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'A review must be approved or rejected.']);
        }

        return DB::transaction(function () use ($moderator, $review, $status): FreelancerReview {
            $review = FreelancerReview::query()->lockForUpdate()->findOrFail($review->id);
            Gate::forUser($moderator)->authorize('moderate', FreelancerReview::class);
            if ($review->moderation_status !== FreelancerReviewStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'This review has already been moderated.']);
            }

            $review->update(['moderation_status' => $status, 'moderated_by' => $moderator->id, 'moderated_at' => now()]);
            AuditLog::query()->create(['actor_id' => $moderator->id, 'subject_id' => $review->project->freelancerProfile?->talentProfile?->user_id, 'event' => 'freelancer.review_moderated', 'metadata' => ['review_public_id' => $review->public_id, 'decision' => $status->value]]);

            return $review->fresh(['project.freelancerProfile.talentProfile']);
        });
    }
}
