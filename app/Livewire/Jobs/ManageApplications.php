<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Domain\Jobs\Actions\ReviewJobApplication;
use App\Enums\JobApplicationStatus;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ManageApplications extends Component
{
    use WithPagination;

    #[Locked]
    public int $jobId;

    /** @var array<string, string> */
    public array $notes = [];

    public function mount(Job $job): void
    {
        $this->jobId = $job->id;
        Gate::authorize('viewApplications', $job);
    }

    public function updateStatus(string $applicationId, string $status, ReviewJobApplication $reviewJobApplication): void
    {
        $job = $this->job();
        Gate::authorize('viewApplications', $job);
        $application = JobApplication::query()->where('public_id', $applicationId)->where('job_id', $job->id)->firstOrFail();
        $validated = Validator::make([
            'status' => $status,
            'note' => $this->notes[$applicationId] ?? '',
        ], [
            'status' => ['required', 'in:reviewing,shortlisted,interview,offer,hired,rejected'],
            'note' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $reviewJobApplication->execute($application, auth()->user(), JobApplicationStatus::from($validated['status']), $validated['note'] ?: null);
        unset($this->notes[$applicationId]);
        session()->flash('status', 'Application status updated.');
    }

    public function render(): View
    {
        $job = $this->job();
        Gate::authorize('viewApplications', $job);

        return view('livewire.jobs.manage-applications', [
            'job' => $job->load('company'),
            'applications' => $job->applications()->with(['candidate.profile', 'statusChanges'])->latest('submitted_at')->paginate(12),
            'statuses' => JobApplicationStatus::cases(),
        ])->extends('layouts.livewire', ['title' => 'Applications · '.$job->title.' · M Talent']);
    }

    private function job(): Job
    {
        return Job::query()->findOrFail($this->jobId);
    }
}
