<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Models\AuditLog;
use App\Models\FreelancerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class EnableFreelancerProfile
{
    public function execute(User $user): FreelancerProfile
    {
        Gate::forUser($user)->authorize('enableFreelancer', $user);

        return DB::transaction(function () use ($user): FreelancerProfile {
            $role = Role::query()->where('name', 'freelancer')->firstOrFail();
            $user->roles()->syncWithoutDetaching([$role->id]);
            $talentProfile = $user->profile()->firstOrCreate([], ['visibility' => 'private']);
            $profile = FreelancerProfile::query()->firstOrCreate(['user_profile_id' => $talentProfile->id]);
            AuditLog::query()->create(['actor_id' => $user->id, 'subject_id' => $user->id, 'event' => 'freelancer.profile_enabled']);

            return $profile->load('talentProfile.user');
        });
    }
}
