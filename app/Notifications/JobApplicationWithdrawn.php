<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Notifications\Notification;

class JobApplicationWithdrawn extends Notification
{
    public function __construct(private readonly JobApplication $application) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'A candidate withdrew from '.$this->application->job->title.'.',
            'url' => route('employer.jobs.applications', $this->application->job, false),
            'job_id' => $this->application->job->public_id,
            'application_id' => $this->application->public_id,
            'status' => 'withdrawn',
        ];
    }
}
