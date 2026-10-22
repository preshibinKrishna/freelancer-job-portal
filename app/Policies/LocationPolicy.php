<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('location.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('location.manage');
    }

    public function update(User $user, Location $location): bool
    {
        return $user->hasPermission('location.manage');
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->hasPermission('location.manage');
    }
}
