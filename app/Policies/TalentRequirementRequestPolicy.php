<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\TalentRequirementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TalentRequirementRequestPolicy
{
    public function create(User $actor, Company $company): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && Gate::forUser($actor)->allows('manageJobs', $company);
    }

    public function view(User $actor, TalentRequirementRequest $request): bool
    {
        if (! $actor->isActive() || ! $actor->hasVerifiedEmail()) {
            return false;
        }

        return $actor->hasRole('super_admin')
            || $request->company->owner_id === $actor->id
            || $request->company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists();
    }
}
