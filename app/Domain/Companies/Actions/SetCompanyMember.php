<?php

declare(strict_types=1);

namespace App\Domain\Companies\Actions;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetCompanyMember
{
    public function addByEmail(Company $company, User $actor, string $email, CompanyMemberRole $role): CompanyMembership
    {
        return DB::transaction(function () use ($company, $actor, $email, $role): CompanyMembership {
            $user = User::query()->where('email', mb_strtolower(trim($email)))->whereNotNull('email_verified_at')->first();
            if ($user === null || ! $user->isActive()) {
                throw ValidationException::withMessages(['memberEmail' => 'No active, verified account was found for that email.']);
            }
            if ($role === CompanyMemberRole::Owner) {
                throw ValidationException::withMessages(['memberRole' => 'Ownership can only be established when creating a company.']);
            }
            if ($company->owner_id === $user->id) {
                throw ValidationException::withMessages(['memberEmail' => 'The company owner cannot be reassigned as another team role.']);
            }

            $membership = CompanyMembership::query()->updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                ['role' => $role, 'status' => CompanyMembershipStatus::Active, 'invited_by' => $actor->id],
            );
            $employer = Role::query()->where('name', 'employer')->firstOrFail();
            $user->roles()->syncWithoutDetaching([$employer->id => ['assigned_by' => $actor->id]]);
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'company.member_added',
                'metadata' => ['company_id' => $company->id, 'member_id' => $user->id, 'role' => $role->value],
            ]);

            return $membership;
        });
    }

    public function remove(Company $company, User $actor, int $userId): void
    {
        DB::transaction(function () use ($company, $actor, $userId): void {
            $membership = $company->memberships()->lockForUpdate()->where('user_id', $userId)->firstOrFail();
            if ($membership->role === CompanyMemberRole::Owner || $company->owner_id === $userId) {
                throw ValidationException::withMessages(['member' => 'The company owner cannot be removed. Transfer ownership before changing this member.']);
            }

            $membership->delete();
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'company.member_removed',
                'metadata' => ['company_id' => $company->id, 'member_id' => $userId],
            ]);
        });
    }
}
