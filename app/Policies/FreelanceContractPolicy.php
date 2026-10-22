<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FreelanceContract;
use App\Models\User;

class FreelanceContractPolicy
{
    public function view(User $actor, FreelanceContract $contract): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail()
            && ($actor->id === $contract->client_id || $actor->id === $contract->freelancer_id
                || ($actor->id === $contract->company?->owner_id)
                || $contract->company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists());
    }

    public function manage(User $actor, FreelanceContract $contract): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail()
            && ($actor->id === $contract->client_id || $contract->company->memberships()->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])->exists());
    }

    public function accept(User $actor, FreelanceContract $contract): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $actor->id === $contract->freelancer_id;
    }
}
