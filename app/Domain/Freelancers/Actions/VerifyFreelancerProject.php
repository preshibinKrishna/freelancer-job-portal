<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Enums\FreelancerProjectVerificationStatus;
use App\Models\AuditLog;
use App\Models\FreelancerProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class VerifyFreelancerProject
{
    public function execute(FreelancerProject $project, User $actor, FreelancerProjectVerificationStatus $decision = FreelancerProjectVerificationStatus::Verified): FreelancerProject
    {
        Gate::forUser($actor)->authorize('verify', $project);
        if (! in_array($decision, [FreelancerProjectVerificationStatus::Verified, FreelancerProjectVerificationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['project' => 'Choose a confirmation decision.']);
        }

        return DB::transaction(function () use ($actor, $decision, $project): FreelancerProject {
            $project = FreelancerProject::query()->lockForUpdate()->findOrFail($project->id);
            Gate::forUser($actor)->authorize('verify', $project);
            if ($project->verification_status !== FreelancerProjectVerificationStatus::Pending) {
                throw ValidationException::withMessages(['project' => 'Only pending project confirmation requests can be reviewed.']);
            }

            $project->update(['verification_status' => $decision, 'verified_by' => $decision === FreelancerProjectVerificationStatus::Verified ? $actor->id : null, 'verified_at' => $decision === FreelancerProjectVerificationStatus::Verified ? now() : null]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->freelancerProfile?->talentProfile?->user_id, 'event' => 'freelancer.project_verification_reviewed', 'metadata' => ['project_public_id' => $project->public_id, 'decision' => $decision->value]]);

            return $project->fresh(['freelancerProfile.talentProfile', 'requestedCompany']);
        });
    }
}
