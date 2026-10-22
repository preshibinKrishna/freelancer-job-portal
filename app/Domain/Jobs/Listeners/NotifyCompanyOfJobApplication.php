<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Listeners;

use App\Domain\Jobs\Events\JobApplicationSubmitted;
use App\Models\JobApplication;
use App\Notifications\JobApplicationReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyCompanyOfJobApplication implements ShouldQueue
{
    public function handle(JobApplicationSubmitted $event): void
    {
        $application = JobApplication::query()->with(['job.company'])->find($event->applicationId);
        if ($application === null) {
            return;
        }

        $recipients = $application->job->company->memberships()
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'admin', 'recruiter'])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
        Notification::send($recipients, new JobApplicationReceived($application));
    }
}
