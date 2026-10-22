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

final class WithdrawProjectProposal
{
    public function execute(ProjectProposal $proposal, User $actor): ProjectProposal
    {
        Gate::forUser($actor)->authorize('withdraw', $proposal);

        return DB::transaction(function () use ($actor, $proposal): ProjectProposal {
            $proposal = ProjectProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            Gate::forUser($actor)->authorize('withdraw', $proposal);
            if (! in_array($proposal->status, [ProjectProposalStatus::Submitted, ProjectProposalStatus::Shortlisted], true)) {
                throw ValidationException::withMessages(['proposal' => 'This proposal can no longer be withdrawn.']);
            }
            $from = $proposal->status;
            $proposal->update(['status' => ProjectProposalStatus::Withdrawn, 'withdrawn_at' => now()]);
            DB::table('project_proposal_status_changes')->insert(['project_proposal_id' => $proposal->id, 'actor_id' => $actor->id, 'from_status' => $from->value, 'to_status' => ProjectProposalStatus::Withdrawn->value, 'created_at' => now()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $proposal->engagement_project_id, 'event' => 'project.proposal_withdrawn', 'metadata' => ['proposal_public_id' => $proposal->public_id]]);

            return $proposal->fresh();
        });
    }
}
