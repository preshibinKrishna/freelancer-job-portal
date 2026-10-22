<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function view(User $user, JobApplication $application): bool
    {
        return $application->candidate_id === $user->id
            || $user->can('manageApplications', $application->job->company);
    }

    public function review(User $user, JobApplication $application): bool
    {
        return $user->can('manageApplications', $application->job->company);
    }

    public function withdraw(User $user, JobApplication $application): bool
    {
        return $application->candidate_id === $user->id
            && $user->isActive()
            && $user->hasVerifiedEmail()
            && ! in_array($application->status, [JobApplicationStatus::Hired, JobApplicationStatus::Rejected, JobApplicationStatus::Withdrawn], true);
    }
}
