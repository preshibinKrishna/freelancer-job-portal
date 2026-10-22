<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Listeners;

use App\Domain\Jobs\Events\JobApplicationStatusChanged;
use App\Models\JobApplication;
use App\Notifications\JobApplicationStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCandidateOfApplicationStatus implements ShouldQueue
{
    public function handle(JobApplicationStatusChanged $event): void
    {
        $application = JobApplication::query()->with(['candidate', 'job'])->find($event->applicationId);
        if ($application !== null) {
            $application->candidate->notify(new JobApplicationStatusUpdated($application));
        }
    }
}
