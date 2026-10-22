<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Enums\FreelancerVerificationStatus;
use App\Models\AuditLog;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SaveFreelancerVerificationRequest
{
    public function execute(FreelancerProfile $profile, User $actor): FreelancerProfile
    {
        Gate::forUser($actor)->authorize('requestVerification', $profile);
        if ($profile->verification_status === FreelancerVerificationStatus::Pending || $profile->verification_status === FreelancerVerificationStatus::Verified) {
            throw ValidationException::withMessages(['verification' => 'Your current verification request cannot be changed.']);
        }

        return DB::transaction(function () use ($actor, $profile): FreelancerProfile {
            $profile = FreelancerProfile::query()->lockForUpdate()->findOrFail($profile->id);
            Gate::forUser($actor)->authorize('requestVerification', $profile);
            if ($profile->verification_status === FreelancerVerificationStatus::Pending || $profile->verification_status === FreelancerVerificationStatus::Verified) {
                throw ValidationException::withMessages(['verification' => 'Your current verification request cannot be changed.']);
            }
            $profile->update(['verification_status' => FreelancerVerificationStatus::Pending, 'verified_by' => null, 'verified_at' => null]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $actor->id, 'event' => 'freelancer.verification_requested']);

            return $profile->fresh();
        });
    }
}
