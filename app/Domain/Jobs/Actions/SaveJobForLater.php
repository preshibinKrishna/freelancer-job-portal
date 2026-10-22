<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class SaveJobForLater
{
    public function toggle(Job $job, User $candidate): bool
    {
        $job = Job::query()->findOrFail($job->id);
        Gate::forUser($candidate)->authorize('save', $job);
        $exists = $candidate->savedJobs()->whereKey($job->id)->exists();
        if ($exists) {
            $candidate->savedJobs()->detach($job->id);

            return false;
        }

        $candidate->savedJobs()->syncWithoutDetaching([$job->id]);

        return true;
    }
}
