<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Enums\FreelancerProjectVerificationStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\FreelancerProfile;
use App\Models\FreelancerProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SaveFreelancerProject
{
    /** @param array{project_category_id: int, title: string, summary: string, role: string|null, project_url: string|null, completed_at: string|null, requested_company_id: int|null} $attributes */
    public function execute(FreelancerProfile $profile, User $actor, array $attributes, ?FreelancerProject $project = null): FreelancerProject
    {
        Gate::forUser($actor)->authorize('update', $profile);
        if ($project !== null && $project->freelancer_profile_id !== $profile->id) {
            Gate::forUser($actor)->authorize('update', $project);
        }
        if ($attributes['requested_company_id'] !== null && ! Company::query()->whereKey($attributes['requested_company_id'])->whereNotNull('published_at')->exists()) {
            throw ValidationException::withMessages(['requested_company_id' => 'Choose a published company to confirm this project.']);
        }
        if ($project?->verification_status === FreelancerProjectVerificationStatus::Verified) {
            throw ValidationException::withMessages(['project' => 'Company-confirmed work cannot be edited. Contact the confirming company if details need correction.']);
        }

        return DB::transaction(function () use ($actor, $attributes, $profile, $project): FreelancerProject {
            $row = $project ?? new FreelancerProject;
            $row->fill([...$attributes, 'verification_status' => $attributes['requested_company_id'] === null ? FreelancerProjectVerificationStatus::Unverified : FreelancerProjectVerificationStatus::Pending]);
            $row->freelancerProfile()->associate($profile);
            $row->save();
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $actor->id, 'event' => $project === null ? 'freelancer.project_added' : 'freelancer.project_updated', 'metadata' => ['project_public_id' => $row->public_id]]);

            return $row->load(['category', 'requestedCompany']);
        });
    }
}
