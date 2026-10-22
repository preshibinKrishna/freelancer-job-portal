<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JobApplicationReceived extends Notification
{
    use Queueable;

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
            'message' => 'A candidate applied to '.$this->application->job->title.'.',
            'url' => route('employer.jobs.applications', $this->application->job, false),
            'job_id' => $this->application->job->public_id,
            'application_id' => $this->application->public_id,
            'status' => $this->application->status->value,
        ];
    }
}
