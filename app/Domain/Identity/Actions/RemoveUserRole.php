<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RemoveUserRole
{
    public function handle(User $user, Role $role, User $actor): void
    {
        Gate::forUser($actor)->authorize('assignRoles', $user);

        if (in_array($role->name, ['admin', 'super_admin'], true) && ! $actor->hasRole('super_admin')) {
            abort(403);
        }

        DB::transaction(function () use ($user, $role, $actor): void {
            if ($role->name === 'super_admin') {
                $lockedRole = DB::table('roles')->where('id', $role->getKey())->lockForUpdate()->first();
                $activeCount = DB::table('role_user')
                    ->join('users', 'users.id', '=', 'role_user.user_id')
                    ->where('role_user.role_id', $lockedRole?->id)
                    ->where('users.status', 'active')
                    ->whereNull('users.deleted_at')
                    ->count();

                if ($activeCount <= 1) {
                    throw ValidationException::withMessages(['role' => 'At least one active super administrator must remain.']);
                }
            }

            $user->roles()->detach($role->getKey());
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'subject_id' => $user->getKey(),
                'event' => 'identity.role_removed',
                'metadata' => ['role' => $role->name],
            ]);
        });
    }
}
