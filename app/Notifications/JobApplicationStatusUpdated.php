<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JobApplicationStatusUpdated extends Notification
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
            'message' => 'Your application for '.$this->application->job->title.' is now '.$this->application->status->value.'.',
            'url' => route('candidate.applications.index', [], false),
            'job_id' => $this->application->job->public_id,
            'application_id' => $this->application->public_id,
            'status' => $this->application->status->value,
        ];
    }
}
