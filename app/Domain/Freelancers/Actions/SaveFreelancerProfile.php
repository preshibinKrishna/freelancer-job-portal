<?php

declare(strict_types=1);

namespace App\Domain\Freelancers\Actions;

use App\Models\AuditLog;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SaveFreelancerProfile
{
    /** @param array{hourly_rate: numeric-string|null, project_rate: numeric-string|null, currency: string|null, weekly_capacity: int|null, is_listed: bool, availability: string|null, category_ids: list<int>} $attributes */
    public function execute(FreelancerProfile $profile, User $actor, array $attributes): FreelancerProfile
    {
        Gate::forUser($actor)->authorize('update', $profile);
        $profile->loadMissing('talentProfile');
        if ($attributes['is_listed'] && $profile->talentProfile?->visibility !== 'public') {
            throw ValidationException::withMessages(['is_listed' => 'Make your professional profile public before listing your freelancer profile.']);
        }

        return DB::transaction(function () use ($actor, $attributes, $profile): FreelancerProfile {
            $profile->update([
                'hourly_rate' => $attributes['hourly_rate'],
                'project_rate' => $attributes['project_rate'],
                'currency' => ($attributes['hourly_rate'] !== null || $attributes['project_rate'] !== null) ? strtoupper((string) $attributes['currency']) : null,
                'weekly_capacity' => $attributes['weekly_capacity'],
                'is_listed' => $attributes['is_listed'],
            ]);
            $profile->categories()->sync($attributes['category_ids']);
            $profile->talentProfile()->update(['availability' => $attributes['availability']]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $actor->id, 'event' => 'freelancer.profile_updated', 'metadata' => ['listed' => $profile->is_listed]]);

            return $profile->fresh(['talentProfile', 'categories', 'projects.category']);
        });
    }
}
