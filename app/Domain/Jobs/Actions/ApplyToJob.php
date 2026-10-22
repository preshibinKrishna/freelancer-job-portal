<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Domain\Jobs\Events\JobApplicationSubmitted;
use App\Enums\JobApplicationStatus;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApplyToJob
{
    public function execute(Job $job, User $candidate, ?string $coverLetter): JobApplication
    {
        return DB::transaction(function () use ($job, $candidate, $coverLetter): JobApplication {
            $job = Job::query()->lockForUpdate()->findOrFail($job->id);
            Gate::forUser($candidate)->authorize('apply', $job);
            if (! $job->acceptsApplications()) {
                throw ValidationException::withMessages(['job' => 'This job is not accepting applications.']);
            }
            if ($job->applications()->where('candidate_id', $candidate->id)->exists()) {
                throw ValidationException::withMessages(['application' => 'You have already applied to this job.']);
            }

            $application = $job->applications()->create([
                'candidate_id' => $candidate->id,
                'cover_letter' => $coverLetter ? trim($coverLetter) : null,
                'status' => JobApplicationStatus::Applied,
                'submitted_at' => now(),
            ]);
            $application->statusChanges()->create([
                'actor_id' => $candidate->id,
                'from_status' => null,
                'to_status' => JobApplicationStatus::Applied->value,
            ]);
            AuditLog::query()->create([
                'actor_id' => $candidate->id,
                'event' => 'job.application_submitted',
                'metadata' => ['application_id' => $application->public_id, 'job_id' => $job->public_id],
            ]);
            JobApplicationSubmitted::dispatch($application->id);

            return $application;
        });
    }
}
