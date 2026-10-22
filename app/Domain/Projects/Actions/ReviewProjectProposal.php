<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Enums\ProjectProposalStatus;
use App\Models\AuditLog;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReviewProjectProposal
{
    public function execute(ProjectProposal $proposal, User $actor, ProjectProposalStatus $status): ProjectProposal
    {
        Gate::forUser($actor)->authorize('review', $proposal);
        if (! in_array($status, [ProjectProposalStatus::Shortlisted, ProjectProposalStatus::Rejected], true)) {
            throw ValidationException::withMessages(['status' => 'Choose shortlist or reject.']);
        }

        return DB::transaction(function () use ($actor, $proposal, $status): ProjectProposal {
            $proposal = ProjectProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            Gate::forUser($actor)->authorize('review', $proposal);
            if (! in_array($proposal->status, [ProjectProposalStatus::Submitted, ProjectProposalStatus::Shortlisted], true)) {
                throw ValidationException::withMessages(['proposal' => 'This proposal is no longer open for review.']);
            }
            $from = $proposal->status;
            $proposal->update(['status' => $status]);
            DB::table('project_proposal_status_changes')->insert(['project_proposal_id' => $proposal->id, 'actor_id' => $actor->id, 'from_status' => $from->value, 'to_status' => $status->value, 'created_at' => now()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $proposal->engagement_project_id, 'event' => 'project.proposal_reviewed', 'metadata' => ['proposal_public_id' => $proposal->public_id, 'status' => $status->value]]);

            return $proposal->fresh(['freelancer', 'project']);
        });
    }
}
