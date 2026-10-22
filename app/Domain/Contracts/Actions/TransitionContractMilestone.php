<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Events\ContractMilestoneStatusChanged;
use App\Enums\ContractMilestoneStatus;
use App\Enums\FreelanceContractStatus;
use App\Models\AuditLog;
use App\Models\ContractMilestone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class TransitionContractMilestone
{
    public function execute(ContractMilestone $milestone, User $actor, ContractMilestoneStatus $to): ContractMilestone
    {
        $milestone->loadMissing('contract.company');
        Gate::forUser($actor)->authorize('view', $milestone->contract);

        return DB::transaction(function () use ($actor, $milestone, $to): ContractMilestone {
            $milestone = ContractMilestone::query()->lockForUpdate()->with('contract.company')->findOrFail($milestone->id);
            $contract = $milestone->contract;
            Gate::forUser($actor)->authorize('view', $contract);
            if ($contract->status !== FreelanceContractStatus::Active) {
                throw ValidationException::withMessages(['milestone' => 'Milestones can only change while the contract is active.']);
            }
            $from = $milestone->status;
            $client = Gate::forUser($actor)->allows('manage', $contract);
            $freelancer = $actor->id === $contract->freelancer_id;
            $allowed = match ($from) {
                ContractMilestoneStatus::Pending => $to === ContractMilestoneStatus::Funded && $client,
                ContractMilestoneStatus::Funded => $to === ContractMilestoneStatus::InProgress && $freelancer,
                ContractMilestoneStatus::InProgress => $to === ContractMilestoneStatus::Submitted && $freelancer,
                ContractMilestoneStatus::Submitted => ($to === ContractMilestoneStatus::Approved && $client)
                    || ($to === ContractMilestoneStatus::Disputed && ($client || $freelancer)),
                ContractMilestoneStatus::Disputed => $to === ContractMilestoneStatus::InProgress && ($client || $freelancer),
                ContractMilestoneStatus::Approved => $to === ContractMilestoneStatus::Released && $client,
                ContractMilestoneStatus::Released => false,
            };
            if (! $allowed) {
                throw ValidationException::withMessages(['milestone' => 'That milestone transition is not allowed for your role.']);
            }
            $milestone->update([
                'status' => $to,
                'submitted_at' => $to === ContractMilestoneStatus::Submitted ? now() : $milestone->submitted_at,
                'approved_at' => $to === ContractMilestoneStatus::Approved ? now() : $milestone->approved_at,
                'released_at' => $to === ContractMilestoneStatus::Released ? now() : $milestone->released_at,
            ]);
            DB::table('contract_milestone_status_changes')->insert(['contract_milestone_id' => $milestone->id, 'actor_id' => $actor->id, 'from_status' => $from->value, 'to_status' => $to->value, 'created_at' => now()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $contract->engagement_project_id, 'event' => 'contract.milestone_status_changed', 'metadata' => ['milestone_public_id' => $milestone->public_id, 'from' => $from->value, 'to' => $to->value]]);
            ContractMilestoneStatusChanged::dispatch($milestone->id, $from->value, $to->value);

            return $milestone->fresh();
        });
    }
}
