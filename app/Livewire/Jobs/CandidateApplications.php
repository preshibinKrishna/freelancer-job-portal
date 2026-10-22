<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Domain\Jobs\Actions\WithdrawJobApplication;
use App\Enums\JobApplicationStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CandidateApplications extends Component
{
    use WithPagination;

    public function withdraw(string $applicationId, WithdrawJobApplication $withdrawJobApplication): void
    {
        $application = Auth::user()->jobApplications()->where('public_id', $applicationId)->firstOrFail();
        $withdrawJobApplication->execute($application, Auth::user());
        session()->flash('status', 'Application withdrawn.');
    }

    public function render(): View
    {
        $applications = Auth::user()->jobApplications()->with(['job.company', 'job.location', 'job.itPark', 'statusChanges'])
            ->latest('submitted_at')->paginate(12);

        return view('livewire.jobs.candidate-applications', [
            'applications' => $applications,
            'withdrawableStatuses' => [JobApplicationStatus::Applied, JobApplicationStatus::Reviewing, JobApplicationStatus::Shortlisted, JobApplicationStatus::Interview, JobApplicationStatus::Offer],
        ])->extends('layouts.livewire', ['title' => 'Your applications · M Talent']);
    }
}
