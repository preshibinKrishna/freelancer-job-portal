<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Services\InvalidateUserSessions;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DeleteAccount
{
    public function __construct(private readonly InvalidateUserSessions $invalidateSessions) {}

    public function handle(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $user);

        DB::transaction(function () use ($user, $actor): void {
            if ($user->hasRole('super_admin')) {
                $this->ensureAnotherSuperAdminRemains();
            }

            $this->invalidateSessions->handle($user);
            $user->profile()->update(['headline' => null, 'bio' => null, 'location' => null]);
            $user->identities()->delete();
            $user->roles()->detach();
            $user->forceFill([
                'name' => 'Deleted account',
                'email' => 'deleted+'.Str::ulid().'@example.invalid',
                'password' => Str::random(64),
                'session_version' => $user->session_version + 1,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
            $user->delete();
            AuditLog::query()->create(['actor_id' => $actor->getKey(), 'subject_id' => $user->getKey(), 'event' => 'account.deleted']);
        });
    }

    private function ensureAnotherSuperAdminRemains(): void
    {
        $role = DB::table('roles')->where('name', 'super_admin')->lockForUpdate()->first();
        $activeCount = DB::table('role_user')
            ->join('users', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', $role?->id)
            ->where('users.status', 'active')
            ->whereNull('users.deleted_at')
            ->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages(['account' => 'At least one active super administrator must remain.']);
        }
    }
}
