<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Events\ContractCreated;
use App\Enums\FreelanceContractStatus;
use App\Enums\ProjectProposalStatus;
use App\Models\AuditLog;
use App\Models\FreelanceContract;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateContractFromAward
{
    /** @param array{start_date: string, end_date: string|null, terms: string} $data */
    public function execute(ProjectProposal $proposal, User $actor, array $data): FreelanceContract
    {
        Gate::forUser($actor)->authorize('review', $proposal);
        $proposal->loadMissing(['project.company', 'milestones']);
        if ($proposal->status !== ProjectProposalStatus::Awarded || $proposal->project->status->value !== 'awarded') {
            throw ValidationException::withMessages(['contract' => 'Only the awarded proposal can become a contract.']);
        }
        if ($data['end_date'] !== null && $data['end_date'] < $data['start_date']) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be after the start date.']);
        }

        return DB::transaction(function () use ($actor, $proposal, $data): FreelanceContract {
            $project = $proposal->project()->lockForUpdate()->firstOrFail();
            if ($project->status->value !== 'awarded' || FreelanceContract::query()->where('engagement_project_id', $project->id)->exists()) {
                throw ValidationException::withMessages(['contract' => 'This project already has a contract or is no longer available.']);
            }
            $contract = FreelanceContract::query()->create([
                'engagement_project_id' => $project->id,
                'project_proposal_id' => $proposal->id,
                'company_id' => $project->company_id,
                'client_id' => $actor->id,
                'freelancer_id' => $proposal->freelancer_id,
                'agreed_amount' => $proposal->proposed_price,
                'currency' => $proposal->currency,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'terms' => $data['terms'],
                'status' => FreelanceContractStatus::Draft,
            ]);
            foreach ($proposal->milestones as $milestone) {
                $contract->milestones()->create($milestone->only(['title', 'description', 'amount', 'duration_days', 'position']));
            }
            DB::table('contract_status_changes')->insert(['freelance_contract_id' => $contract->id, 'actor_id' => $actor->id, 'from_status' => null, 'to_status' => FreelanceContractStatus::Draft->value, 'created_at' => now()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->id, 'event' => 'contract.created', 'metadata' => ['contract_public_id' => $contract->public_id, 'proposal_public_id' => $proposal->public_id]]);
            ContractCreated::dispatch($contract->id);

            return $contract->load('milestones');
        });
    }
}
