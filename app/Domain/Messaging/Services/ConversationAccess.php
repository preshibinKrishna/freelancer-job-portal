<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Models\Conversation;
use App\Models\JobApplication;
use App\Models\ProjectProposal;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ConversationAccess
{
    public function canStart(User $actor, Model $context): bool
    {
        return match (true) {
            $context instanceof JobApplication => $context->candidate_id === $actor->id || $actor->can('manageApplications', $context->job->company),
            $context instanceof ProjectProposal => $context->freelancer_id === $actor->id || $actor->can('manage', $context->project),
            $context instanceof RecruitmentPipelineEntry => $context->candidate_id === $actor->id || $context->recruitmentRequest->assigned_recruiter_id === $actor->id,
            $context instanceof RecruitmentRequest => $context->created_by === $actor->id || $context->assigned_recruiter_id === $actor->id || $actor->can('manage', $context),
            default => false,
        };
    }

    /** @return list<User> */
    public function participants(User $actor, Model $context): array
    {
        $other = match (true) {
            $context instanceof JobApplication => $context->candidate_id === $actor->id ? $context->job->creator : $context->candidate,
            $context instanceof ProjectProposal => $context->freelancer_id === $actor->id ? $context->project->creator : $context->freelancer,
            $context instanceof RecruitmentPipelineEntry => $context->candidate_id === $actor->id ? $context->recruitmentRequest->assignedRecruiter : $context->candidate,
            $context instanceof RecruitmentRequest => $context->created_by === $actor->id ? $context->assignedRecruiter : $context->creator,
            default => null,
        };

        if (! $other instanceof User || $other->is($actor)) {
            return [];
        }

        return [$actor, $other];
    }

    public function canView(User $user, Conversation $conversation): bool
    {
        if (! $conversation->participants()->whereKey($user->id)->exists()) {
            return false;
        }

        $context = $conversation->context;

        return $context instanceof Model && $this->canStart($user, $context);
    }

    public function isBlocked(User $user, User $other): bool
    {
        return $user->blocks()->where('blocked_id', $other->id)->exists()
            || $user->blockedBy()->where('blocker_id', $other->id)->exists();
    }
}
