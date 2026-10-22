<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyVerification;
use App\Models\User;

class CompanyPolicy
{
    public function view(?User $user, Company $company): bool
    {
        return $company->isPublished()
            || ($user !== null && ($this->isManager($user, $company) || $company->memberships()->where('user_id', $user->id)->where('status', 'active')->exists() || $user->hasPermission('company.verify')));
    }

    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail();
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail();
    }

    public function update(User $user, Company $company): bool
    {
        return $this->isManager($user, $company);
    }

    public function manageMembers(User $user, Company $company): bool
    {
        return $this->isManager($user, $company);
    }

    public function submitVerification(User $user, Company $company): bool
    {
        return $this->isManager($user, $company);
    }

    public function manageJobs(User $user, Company $company): bool
    {
        return $this->isManager($user, $company)
            || $company->memberships()->where('user_id', $user->id)->where('status', 'active')->where('role', 'recruiter')->exists();
    }

    public function manageApplications(User $user, Company $company): bool
    {
        return $this->manageJobs($user, $company);
    }

    public function reviewVerification(User $user, CompanyVerification $verification): bool
    {
        return $user->hasPermission('company.verify');
    }

    private function isManager(User $user, Company $company): bool
    {
        return $company->owner_id === $user->id
            || $company->memberships()->where('user_id', $user->id)->where('status', 'active')->whereIn('role', ['owner', 'admin'])->exists();
    }
}
