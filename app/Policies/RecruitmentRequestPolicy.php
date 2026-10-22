<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RecruitmentRequestPolicy
{
    public function create(User $actor, Company $company): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && Gate::forUser($actor)->allows('manageJobs', $company);
    }

    public function view(User $actor, RecruitmentRequest $request): bool
    {
        if (! $actor->isActive() || ! $actor->hasVerifiedEmail()) {
            return false;
        }

        return $this->isCompanyTeam($actor, $request->company)
            || $actor->hasRole('super_admin')
            || ($actor->hasRole('recruiter') && ($request->assigned_recruiter_id === null || $request->assigned_recruiter_id === $actor->id));
    }

    public function claim(User $actor, RecruitmentRequest $request): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $actor->hasRole('recruiter')
            && ($request->assigned_recruiter_id === null || $request->assigned_recruiter_id === $actor->id);
    }

    public function managePipeline(User $actor, RecruitmentRequest $request): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail()
            && ($actor->hasRole('super_admin') || ($actor->hasRole('recruiter') && $request->assigned_recruiter_id === $actor->id));
    }

    public function clientReview(User $actor, RecruitmentRequest $request): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $this->isCompanyTeam($actor, $request->company);
    }

    private function isCompanyTeam(User $actor, Company $company): bool
    {
        return $company->owner_id === $actor->id
            || $company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists();
    }
}
