<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Services\InvalidateUserSessions;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ResetUserPassword
{
    public function __construct(private readonly InvalidateUserSessions $invalidateSessions) {}

    public function handle(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'session_version' => $user->session_version + 1,
            ])->save();

            $this->invalidateSessions->handle($user);
            AuditLog::query()->create(['subject_id' => $user->getKey(), 'event' => 'auth.password_reset']);
        });
    }
}
