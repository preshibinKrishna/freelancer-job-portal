<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Events\ContractStatusChanged;
use App\Enums\EngagementProjectStatus;
use App\Enums\FreelanceContractStatus;
use App\Models\AuditLog;
use App\Models\FreelanceContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ActivateContract
{
    public function execute(FreelanceContract $contract, User $actor): FreelanceContract
    {
        Gate::forUser($actor)->authorize('accept', $contract);

        return DB::transaction(function () use ($actor, $contract): FreelanceContract {
            $contract = FreelanceContract::query()->lockForUpdate()->findOrFail($contract->id);
            Gate::forUser($actor)->authorize('accept', $contract);
            if ($contract->status !== FreelanceContractStatus::Draft) {
                throw ValidationException::withMessages(['contract' => 'Only a draft contract can be accepted.']);
            }
            $contract->update(['status' => FreelanceContractStatus::Active, 'activated_at' => now()]);
            DB::table('contract_status_changes')->insert(['freelance_contract_id' => $contract->id, 'actor_id' => $actor->id, 'from_status' => FreelanceContractStatus::Draft->value, 'to_status' => FreelanceContractStatus::Active->value, 'created_at' => now()]);
            $project = $contract->project()->lockForUpdate()->firstOrFail();
            if ($project->status === EngagementProjectStatus::Awarded) {
                $project->update(['status' => EngagementProjectStatus::InProgress]);
                DB::table('engagement_project_status_changes')->insert(['engagement_project_id' => $project->id, 'actor_id' => $actor->id, 'from_status' => EngagementProjectStatus::Awarded->value, 'to_status' => EngagementProjectStatus::InProgress->value, 'created_at' => now()]);
            }
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $contract->engagement_project_id, 'event' => 'contract.activated', 'metadata' => ['contract_public_id' => $contract->public_id]]);
            ContractStatusChanged::dispatch($contract->id, FreelanceContractStatus::Draft->value, FreelanceContractStatus::Active->value);

            return $contract->fresh(['milestones', 'project']);
        });
    }
}
