<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;

class TalentProfilePolicy
{
    public function create(User $actor): bool
    {
        return $actor->isActive() && $actor->hasRole('candidate', 'freelancer');
    }

    public function update(User $actor, UserProfile $profile): bool
    {
        return $actor->isActive() && $profile->user_id === $actor->id;
    }

    public function viewPublic(?User $actor, UserProfile $profile): bool
    {
        return ($profile->visibility === 'public' && $profile->user?->isActive() === true) || ($actor !== null && $profile->user_id === $actor->id && $actor->isActive());
    }
}
