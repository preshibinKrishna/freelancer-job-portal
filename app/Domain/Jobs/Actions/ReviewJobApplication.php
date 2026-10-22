<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Domain\Jobs\Events\JobApplicationStatusChanged;
use App\Enums\JobApplicationStatus;
use App\Models\AuditLog;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewJobApplication
{
    public function execute(JobApplication $application, User $reviewer, JobApplicationStatus $status, ?string $note = null): JobApplication
    {
        if ($status === JobApplicationStatus::Applied || $status === JobApplicationStatus::Withdrawn) {
            throw ValidationException::withMessages(['status' => 'Choose a valid employer review stage.']);
        }

        return DB::transaction(function () use ($application, $reviewer, $status, $note): JobApplication {
            $application = JobApplication::query()->lockForUpdate()->findOrFail($application->id);
            Gate::forUser($reviewer)->authorize('review', $application);
            $from = $application->status;
            if (! in_array($status, $from->employerTransitions(), true)) {
                throw ValidationException::withMessages(['status' => 'That application status transition is not available.']);
            }

            $application->update(['status' => $status]);
            $application->statusChanges()->create([
                'actor_id' => $reviewer->id,
                'from_status' => $from->value,
                'to_status' => $status->value,
                'note' => $note ? trim($note) : null,
            ]);
            AuditLog::query()->create([
                'actor_id' => $reviewer->id,
                'event' => 'job.application_status_changed',
                'metadata' => ['application_id' => $application->public_id, 'from' => $from->value, 'to' => $status->value],
            ]);
            JobApplicationStatusChanged::dispatch($application->id);

            return $application->fresh() ?? $application;
        });
    }
}
