<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Domain\Jobs\Events\JobApplicationWithdrawn;
use App\Enums\JobApplicationStatus;
use App\Models\AuditLog;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WithdrawJobApplication
{
    public function execute(JobApplication $application, User $candidate): JobApplication
    {
        return DB::transaction(function () use ($application, $candidate): JobApplication {
            $application = JobApplication::query()->lockForUpdate()->findOrFail($application->id);
            Gate::forUser($candidate)->authorize('withdraw', $application);
            if ($application->candidate_id !== $candidate->id || in_array($application->status, [JobApplicationStatus::Hired, JobApplicationStatus::Rejected, JobApplicationStatus::Withdrawn], true)) {
                throw ValidationException::withMessages(['application' => 'This application can no longer be withdrawn.']);
            }

            $from = $application->status;
            $application->update(['status' => JobApplicationStatus::Withdrawn, 'withdrawn_at' => now()]);
            $application->statusChanges()->create([
                'actor_id' => $candidate->id,
                'from_status' => $from->value,
                'to_status' => JobApplicationStatus::Withdrawn->value,
            ]);
            AuditLog::query()->create([
                'actor_id' => $candidate->id,
                'event' => 'job.application_withdrawn',
                'metadata' => ['application_id' => $application->public_id, 'job_id' => $application->job_id],
            ]);
            JobApplicationWithdrawn::dispatch($application->id);

            return $application->fresh() ?? $application;
        });
    }
}
