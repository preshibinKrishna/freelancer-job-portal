<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AssignRole
{
    public function handle(User $user, Role $role, User $assignedBy): void
    {
        Gate::forUser($assignedBy)->authorize('assignRoles', $user);

        if (in_array($role->name, ['admin', 'super_admin'], true) && ! $assignedBy->hasRole('super_admin')) {
            abort(403);
        }

        DB::transaction(function () use ($user, $role, $assignedBy): void {
            $user->roles()->syncWithoutDetaching([$role->getKey() => ['assigned_by' => $assignedBy->getKey()]]);
            AuditLog::query()->create([
                'actor_id' => $assignedBy->getKey(),
                'subject_id' => $user->getKey(),
                'event' => 'identity.role_assigned',
                'metadata' => ['role' => $role->name],
            ]);
        });
    }
}
