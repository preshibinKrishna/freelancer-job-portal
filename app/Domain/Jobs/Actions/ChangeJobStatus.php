<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Domain\Jobs\Events\JobPublished;
use App\Enums\JobStatus;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeJobStatus
{
    public function execute(Job $job, User $actor, JobStatus $status): Job
    {
        return DB::transaction(function () use ($job, $actor, $status): Job {
            $job = Job::query()->lockForUpdate()->with('company')->findOrFail($job->id);
            Gate::forUser($actor)->authorize('manage', $job);
            $from = $job->status;
            $allowed = match ($from) {
                JobStatus::Draft => [JobStatus::Published, JobStatus::Closed],
                JobStatus::Published => [JobStatus::Paused, JobStatus::Closed],
                JobStatus::Paused => [JobStatus::Published, JobStatus::Closed],
                JobStatus::Closed => [],
            };

            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'That job status change is not available.']);
            }
            if ($status === JobStatus::Published) {
                if ($job->company === null || ! $job->company->isPublished()) {
                    throw ValidationException::withMessages(['company' => 'Publish the company profile before publishing a job.']);
                }
                if ($job->application_deadline !== null && $job->application_deadline->isPast() && ! $job->application_deadline->isToday()) {
                    throw ValidationException::withMessages(['applicationDeadline' => 'Choose a future application deadline.']);
                }
                if ($job->title === '' || mb_strlen($job->description) < 50) {
                    throw ValidationException::withMessages(['description' => 'Complete the job details before publishing.']);
                }
            }

            $job->update([
                'status' => $status,
                'published_at' => $status === JobStatus::Published ? ($job->published_at ?? now()) : $job->published_at,
                'closed_at' => $status === JobStatus::Closed ? now() : null,
            ]);
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'job.status_changed',
                'metadata' => ['job_id' => $job->public_id, 'from' => $from->value, 'to' => $status->value],
            ]);
            if ($status === JobStatus::Published) {
                JobPublished::dispatch($job->id);
            }

            return $job->fresh(['company', 'skills']) ?? $job;
        });
    }
}
