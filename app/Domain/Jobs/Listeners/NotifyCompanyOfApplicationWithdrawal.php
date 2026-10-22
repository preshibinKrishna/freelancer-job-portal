<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Listeners;

use App\Domain\Jobs\Events\JobApplicationWithdrawn;
use App\Models\JobApplication;
use App\Notifications\JobApplicationWithdrawn as JobApplicationWithdrawnNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyCompanyOfApplicationWithdrawal implements ShouldQueue
{
    public function handle(JobApplicationWithdrawn $event): void
    {
        $application = JobApplication::query()->with('job.company')->find($event->applicationId);
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
        Notification::send($recipients, new JobApplicationWithdrawnNotification($application));
    }
}
