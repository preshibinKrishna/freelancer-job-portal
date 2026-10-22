<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FreelancerProject;
use App\Models\User;

class FreelancerProjectPolicy
{
    public function create(User $actor, FreelancerProject $project): bool
    {
        return $actor->isActive() && $project->freelancerProfile?->talentProfile?->user_id === $actor->id;
    }

    public function update(User $actor, FreelancerProject $project): bool
    {
        return $actor->isActive() && $project->freelancerProfile?->talentProfile?->user_id === $actor->id;
    }

    public function delete(User $actor, FreelancerProject $project): bool
    {
        return $this->update($actor, $project) && ! $project->review()->exists();
    }

    public function verify(User $actor, FreelancerProject $project): bool
    {
        $company = $project->requestedCompany;
        if (! $actor->isActive() || ! $actor->hasVerifiedEmail() || $company === null || $project->completed_at === null) {
            return false;
        }

        return $company->owner_id === $actor->id
            || $company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists();
    }
}
