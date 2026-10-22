<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function enableFreelancer(User $actor, User $target): bool
    {
        return $actor->is($target)
            && $actor->isActive()
            && $actor->hasVerifiedEmail()
            && $actor->hasRole('candidate', 'freelancer');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->is($target) || $actor->hasPermission('user.view');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->is($target) || $actor->hasPermission('user.update');
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return true;
        }

        if ($target->hasRole('admin', 'super_admin')) {
            return $actor->hasRole('super_admin');
        }

        return $actor->hasPermission('user.delete');
    }

    public function assignRoles(User $actor, User $target): bool
    {
        return ! $actor->is($target) && $actor->hasPermission('user.roles.assign');
    }

    public function suspend(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        if ($target->hasRole('admin', 'super_admin')) {
            return $actor->hasRole('super_admin');
        }

        return $actor->hasPermission('user.suspend');
    }
}
