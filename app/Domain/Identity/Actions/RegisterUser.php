<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterUser
{
    /** @param array{name: string, email: string, password: string, account_type?: string} $attributes */
    public function handle(array $attributes): User
    {
        $accountType = $attributes['account_type'] ?? 'candidate';
        if (! in_array($accountType, ['candidate', 'employer'], true)) {
            throw new \InvalidArgumentException('Unsupported account type.');
        }
        unset($attributes['account_type']);

        return DB::transaction(function () use ($attributes, $accountType): User {
            $user = User::query()->create([
                ...$attributes,
                'status' => AccountStatus::Active,
            ]);

            $role = Role::query()->where('name', $accountType)->firstOrFail();
            $user->roles()->attach($role, ['assigned_by' => $user->getKey()]);
            AuditLog::query()->create(['actor_id' => $user->getKey(), 'subject_id' => $user->getKey(), 'event' => 'account.registered']);

            return $user;
        });
    }
}
