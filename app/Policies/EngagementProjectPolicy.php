<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EngagementProjectStatus;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\User;

class EngagementProjectPolicy
{
    public function create(User $actor, Company $company): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $this->managesCompany($actor, $company);
    }

    public function update(User $actor, EngagementProject $project): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail()
            && $this->managesCompany($actor, $project->company)
            && in_array($project->status, [EngagementProjectStatus::Draft, EngagementProjectStatus::Published, EngagementProjectStatus::Reviewing], true);
    }

    public function view(?User $actor, EngagementProject $project): bool
    {
        if ($actor !== null && $actor->isActive() && $actor->hasVerifiedEmail()) {
            if ($this->managesCompany($actor, $project->company)) {
                return true;
            }
            if ($project->proposals()->where('freelancer_id', $actor->id)->exists()) {
                return true;
            }
        }

        return $project->status->isDiscoverable() && $project->company()->whereNotNull('published_at')->exists();
    }

    public function save(User $actor, EngagementProject $project): bool
    {
        return $this->isEligibleFreelancer($actor)
            && ($actor->savedEngagementProjects()->whereKey($project->id)->exists() || $project->status->isDiscoverable());
    }

    public function propose(User $actor, EngagementProject $project): bool
    {
        return $this->isEligibleFreelancer($actor) && $project->status->isDiscoverable()
            && ! $project->proposals()->where('freelancer_id', $actor->id)->exists();
    }

    public function manage(User $actor, EngagementProject $project): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $this->managesCompany($actor, $project->company);
    }

    public function accessAttachment(User $actor, EngagementProject $project): bool
    {
        return $this->manage($actor, $project)
            || ($this->isEligibleFreelancer($actor) && $project->status->isDiscoverable());
    }

    private function isEligibleFreelancer(User $actor): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $actor->hasRole('candidate', 'freelancer');
    }

    private function managesCompany(User $actor, Company $company): bool
    {
        return $company->owner_id === $actor->id
            || $company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists();
    }
}
