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

final class UpdateFreelancerVerification
{
    public function request(FreelancerProfile $profile, User $actor): FreelancerProfile
    {
        Gate::forUser($actor)->authorize('requestVerification', $profile);
        if ($profile->verification_status === FreelancerVerificationStatus::Verified || $profile->verification_status === FreelancerVerificationStatus::Pending) {
            throw ValidationException::withMessages(['verification' => 'Your current verification request cannot be changed.']);
        }

        return DB::transaction(function () use ($actor, $profile): FreelancerProfile {
            $profile->update(['verification_status' => FreelancerVerificationStatus::Pending, 'verified_by' => null, 'verified_at' => null]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $actor->id, 'event' => 'freelancer.verification_requested']);

            return $profile->fresh();
        });
    }

    public function review(FreelancerProfile $profile, User $reviewer, FreelancerVerificationStatus $decision): FreelancerProfile
    {
        Gate::forUser($reviewer)->authorize('verify', FreelancerProfile::class);
        if (! in_array($decision, [FreelancerVerificationStatus::Verified, FreelancerVerificationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['verification' => 'Choose a verification decision.']);
        }

        return DB::transaction(function () use ($decision, $profile, $reviewer): FreelancerProfile {
            $profile = FreelancerProfile::query()->lockForUpdate()->findOrFail($profile->id);
            Gate::forUser($reviewer)->authorize('verify', FreelancerProfile::class);
            if ($profile->verification_status !== FreelancerVerificationStatus::Pending) {
                throw ValidationException::withMessages(['verification' => 'Only pending freelancer profiles can be reviewed.']);
            }

            $profile->update(['verification_status' => $decision, 'verified_by' => $decision === FreelancerVerificationStatus::Verified ? $reviewer->id : null, 'verified_at' => $decision === FreelancerVerificationStatus::Verified ? now() : null]);
            AuditLog::query()->create(['actor_id' => $reviewer->id, 'subject_id' => $profile->talentProfile?->user_id, 'event' => 'freelancer.verification_reviewed', 'metadata' => ['decision' => $decision->value]]);

            return $profile->fresh(['talentProfile.user']);
        });
    }
}
