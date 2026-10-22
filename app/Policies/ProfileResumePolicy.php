<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\ProfileResume;
use App\Models\User;

class ProfileResumePolicy
{
    public function delete(User $actor, ProfileResume $resume): bool
    {
        return $actor->isActive() && $resume->userProfile?->user_id === $actor->id;
    }

    public function download(User $actor, ProfileResume $resume): bool
    {
        $profile = $resume->userProfile;
        if ($profile === null || ! $actor->isActive()) {
            return false;
        }

        if ($profile->user_id === $actor->id) {
            return true;
        }

        if ($resume->scan_status !== 'clean') {
            return false;
        }

        return JobApplication::query()
            ->where('candidate_id', $profile->user_id)
            ->whereNotIn('status', ['withdrawn', 'rejected'])
            ->whereHas('job.company.memberships', fn ($query) => $query->where('user_id', $actor->id)->where('status', 'active'))
            ->exists();
    }
}
