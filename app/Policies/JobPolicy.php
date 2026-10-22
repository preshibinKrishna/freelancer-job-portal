<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail() && (
            $user->ownedCompanies()->exists()
            || $user->companyMemberships()->where('status', 'active')->whereIn('role', ['admin', 'recruiter'])->exists()
        );
    }

    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, Job $job): bool
    {
        if ($this->canManageCompanyJobs($user, $job->company_id)) {
            return true;
        }

        return ($job->status === JobStatus::Published
                && in_array($job->visibility, [JobVisibility::Public, JobVisibility::Unlisted], true)
                && $job->company()->whereNotNull('published_at')->exists())
            || ($user !== null && ($job->applications()->where('candidate_id', $user->id)->exists()
                || $job->savers()->whereKey($user->id)->exists()));
    }

    public function manage(User $user, Job $job): bool
    {
        return $this->canManageCompanyJobs($user, $job->company_id);
    }

    public function viewApplications(User $user, Job $job): bool
    {
        return $this->canManageCompanyJobs($user, $job->company_id);
    }

    public function save(User $user, Job $job): bool
    {
        return $this->isActiveCandidate($user)
            && ($job->savers()->whereKey($user->id)->exists()
                || ($job->status === JobStatus::Published && $job->company()->whereNotNull('published_at')->exists()));
    }

    public function apply(User $user, Job $job): bool
    {
        return $this->save($user, $job) && $job->acceptsApplications();
    }

    private function canManageCompanyJobs(?User $user, int $companyId): bool
    {
        if ($user === null || ! $user->isActive() || ! $user->hasVerifiedEmail()) {
            return false;
        }

        return Company::query()->whereKey($companyId)->where(function ($query) use ($user): void {
            $query->where('owner_id', $user->id)
                ->orWhereHas('memberships', fn ($membership) => $membership->where('user_id', $user->id)
                    ->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter']));
        })->exists();
    }

    private function isActiveCandidate(User $user): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail() && $user->hasRole('candidate');
    }
}
