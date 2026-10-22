<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Services\InvalidateUserSessions;
use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeAccountStatus
{
    public function __construct(private readonly InvalidateUserSessions $invalidateSessions) {}

    public function suspend(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('suspend', $user);

        DB::transaction(function () use ($user, $actor): void {
            if ($user->hasRole('super_admin')) {
                $this->ensureAnotherSuperAdminRemains();
            }

            $user->update(['status' => AccountStatus::Suspended, 'session_version' => $user->session_version + 1]);
            $this->invalidateSessions->handle($user);
            AuditLog::query()->create(['actor_id' => $actor->getKey(), 'subject_id' => $user->getKey(), 'event' => 'account.suspended']);
        });
    }

    public function activate(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('suspend', $user);
        DB::transaction(function () use ($user, $actor): void {
            $user->update(['status' => AccountStatus::Active]);
            AuditLog::query()->create(['actor_id' => $actor->getKey(), 'subject_id' => $user->getKey(), 'event' => 'account.activated']);
        });
    }

    private function ensureAnotherSuperAdminRemains(): void
    {
        $role = DB::table('roles')->where('name', 'super_admin')->lockForUpdate()->first();
        $activeCount = DB::table('role_user')
            ->join('users', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', $role?->id)
            ->where('users.status', AccountStatus::Active->value)
            ->whereNull('users.deleted_at')
            ->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages(['account' => 'At least one active super administrator must remain.']);
        }
    }
}
