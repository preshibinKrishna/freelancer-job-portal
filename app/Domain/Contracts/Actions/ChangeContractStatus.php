<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Events\ContractStatusChanged;
use App\Enums\ContractMilestoneStatus;
use App\Enums\EngagementProjectStatus;
use App\Enums\FreelanceContractStatus;
use App\Models\AuditLog;
use App\Models\FreelanceContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeContractStatus
{
    public function execute(FreelanceContract $contract, User $actor, FreelanceContractStatus $to): FreelanceContract
    {
        Gate::forUser($actor)->authorize('view', $contract);

        return DB::transaction(function () use ($actor, $contract, $to): FreelanceContract {
            $contract = FreelanceContract::query()->lockForUpdate()->with('milestones')->findOrFail($contract->id);
            Gate::forUser($actor)->authorize('view', $contract);
            $from = $contract->status;
            $party = $actor->id === $contract->client_id || $actor->id === $contract->freelancer_id;
            $allowed = ($to === FreelanceContractStatus::Cancelled && $party && in_array($from, [FreelanceContractStatus::Draft, FreelanceContractStatus::Active], true))
                || ($to === FreelanceContractStatus::Completed && $actor->id === $contract->client_id
                    && $from === FreelanceContractStatus::Active
                    && $contract->milestones->every(fn ($milestone): bool => $milestone->status === ContractMilestoneStatus::Released));
            if (! $allowed) {
                throw ValidationException::withMessages(['contract' => 'That contract status change is not allowed.']);
            }
            $contract->update(['status' => $to, 'completed_at' => $to === FreelanceContractStatus::Completed ? now() : null]);
            DB::table('contract_status_changes')->insert(['freelance_contract_id' => $contract->id, 'actor_id' => $actor->id, 'from_status' => $from->value, 'to_status' => $to->value, 'created_at' => now()]);
            $project = $contract->project()->lockForUpdate()->firstOrFail();
            $projectStatus = $to === FreelanceContractStatus::Completed
                ? EngagementProjectStatus::Completed
                : EngagementProjectStatus::Cancelled;
            if ($project->status !== $projectStatus && in_array($project->status, [EngagementProjectStatus::Awarded, EngagementProjectStatus::InProgress], true)) {
                $projectFrom = $project->status;
                $project->update(['status' => $projectStatus]);
                DB::table('engagement_project_status_changes')->insert(['engagement_project_id' => $project->id, 'actor_id' => $actor->id, 'from_status' => $projectFrom->value, 'to_status' => $projectStatus->value, 'created_at' => now()]);
            }
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $contract->engagement_project_id, 'event' => 'contract.status_changed', 'metadata' => ['contract_public_id' => $contract->public_id, 'from' => $from->value, 'to' => $to->value]]);
            ContractStatusChanged::dispatch($contract->id, $from->value, $to->value);

            return $contract->fresh(['milestones', 'project']);
        });
    }
}
