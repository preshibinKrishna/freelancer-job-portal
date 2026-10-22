<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Enums\EngagementProjectStatus;
use App\Enums\ProjectProposalStatus;
use App\Models\AuditLog;
use App\Models\EngagementProject;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AwardProjectProposal
{
    public function execute(ProjectProposal $proposal, User $actor): ProjectProposal
    {
        Gate::forUser($actor)->authorize('review', $proposal);

        return DB::transaction(function () use ($actor, $proposal): ProjectProposal {
            $project = EngagementProject::query()->lockForUpdate()->findOrFail($proposal->engagement_project_id);
            $proposal = ProjectProposal::query()->lockForUpdate()->with('milestones')->findOrFail($proposal->id);
            Gate::forUser($actor)->authorize('review', $proposal);
            if (! in_array($project->status, [EngagementProjectStatus::Published, EngagementProjectStatus::Reviewing], true)
                || ! in_array($proposal->status, [ProjectProposalStatus::Submitted, ProjectProposalStatus::Shortlisted], true)) {
                throw ValidationException::withMessages(['proposal' => 'Only an open proposal on an active project can be awarded.']);
            }
            $projectFrom = $project->status;
            $proposalFrom = $proposal->status;
            $project->update(['status' => EngagementProjectStatus::Awarded]);
            DB::table('engagement_project_status_changes')->insert(['engagement_project_id' => $project->id, 'actor_id' => $actor->id, 'from_status' => $projectFrom->value, 'to_status' => EngagementProjectStatus::Awarded->value, 'created_at' => now()]);
            $proposal->update(['status' => ProjectProposalStatus::Awarded]);
            DB::table('project_proposal_status_changes')->insert(['project_proposal_id' => $proposal->id, 'actor_id' => $actor->id, 'from_status' => $proposalFrom->value, 'to_status' => ProjectProposalStatus::Awarded->value, 'created_at' => now()]);
            foreach ($proposal->milestones as $milestone) {
                $project->milestones()->create($milestone->only(['title', 'description', 'amount', 'duration_days', 'position']));
            }
            $losers = ProjectProposal::query()->where('engagement_project_id', $project->id)->whereKeyNot($proposal->id)->whereIn('status', [ProjectProposalStatus::Submitted, ProjectProposalStatus::Shortlisted])->get();
            foreach ($losers as $loser) {
                $loserFrom = $loser->status;
                $loser->update(['status' => ProjectProposalStatus::Rejected]);
                DB::table('project_proposal_status_changes')->insert(['project_proposal_id' => $loser->id, 'actor_id' => $actor->id, 'from_status' => $loserFrom->value, 'to_status' => ProjectProposalStatus::Rejected->value, 'created_at' => now()]);
            }
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->id, 'event' => 'project.proposal_awarded', 'metadata' => ['proposal_public_id' => $proposal->public_id]]);

            return $proposal->fresh(['project', 'milestones', 'portfolioItems']);
        });
    }
}
