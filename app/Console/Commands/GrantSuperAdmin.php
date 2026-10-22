<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrantSuperAdmin extends Command
{
    protected $signature = 'identity:grant-super-admin {email} {--force : Skip interactive confirmation}';

    protected $description = 'Grant the super_admin capability to an existing verified account';

    public function handle(): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null || ! $user->hasVerifiedEmail() || $user->status !== AccountStatus::Active) {
            $this->error('The email must belong to an active, verified account.');

            return self::FAILURE;
        }

        if ($user->hasRole('super_admin')) {
            $this->info('This account is already a super administrator.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Grant super administrator access to {$user->email}?")) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($user): void {
            $role = Role::query()->where('name', 'super_admin')->lockForUpdate()->firstOrFail();
            $user->roles()->syncWithoutDetaching([$role->getKey() => ['assigned_by' => null]]);
            AuditLog::query()->create([
                'subject_id' => $user->getKey(),
                'event' => 'identity.super_admin_bootstrapped',
            ]);
        });

        $this->info('Super administrator access granted.');

        return self::SUCCESS;
    }
}
