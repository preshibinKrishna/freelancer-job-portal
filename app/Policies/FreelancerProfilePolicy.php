<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FreelancerProfile;
use App\Models\User;

class FreelancerProfilePolicy
{
    public function create(User $actor): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $actor->hasRole('candidate', 'freelancer');
    }

    public function update(User $actor, FreelancerProfile $profile): bool
    {
        return $actor->isActive() && $profile->talentProfile?->user_id === $actor->id;
    }

    public function view(?User $actor, FreelancerProfile $profile): bool
    {
        $owner = $profile->talentProfile?->user;
        if ($actor !== null && $actor->isActive() && $actor->is($owner)) {
            return true;
        }

        return $profile->is_listed
            && $owner?->isActive() === true
            && $owner->hasVerifiedEmail()
            && $profile->talentProfile?->visibility === 'public';
    }

    public function requestVerification(User $actor, FreelancerProfile $profile): bool
    {
        return $actor->isActive() && $profile->talentProfile?->user_id === $actor->id;
    }

    public function verify(User $actor): bool
    {
        return $actor->isActive() && $actor->hasPermission('freelancer.verify');
    }
}
