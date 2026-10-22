<?php

declare(strict_types=1);

namespace App\Domain\Matching\Actions;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\TalentRequirementRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateTalentRequirementRequest
{
    /** @param array<string, mixed> $details */
    public function execute(User $actor, Company $company, string $category, string $solutionType, array $details, array $suggestedSkills): TalentRequirementRequest
    {
        return DB::transaction(function () use ($actor, $company, $category, $solutionType, $details, $suggestedSkills): TalentRequirementRequest {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            Gate::forUser($actor)->authorize('create', [TalentRequirementRequest::class, $company]);
            $catalog = config('talent_requirements.categories');
            if (! isset($catalog[$category]['types'][$solutionType])) {
                throw ValidationException::withMessages(['solutionType' => 'Choose a supported solution type.']);
            }

            $request = TalentRequirementRequest::query()->create([
                'company_id' => $company->id,
                'created_by' => $actor->id,
                'category' => $category,
                'solution_type' => $solutionType,
                'details' => $details,
                'suggested_skills' => $suggestedSkills,
                'submitted_at' => now(),
            ]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $request->id, 'event' => 'talent_requirement.submitted', 'metadata' => ['company_id' => $company->id, 'request_public_id' => $request->public_id, 'category' => $category, 'solution_type' => $solutionType]]);

            return $request->load('company');
        });
    }
}
