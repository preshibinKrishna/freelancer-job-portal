<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Domain\Jobs\Actions\ApplyToJob;
use App\Domain\Jobs\Actions\SaveJobForLater;
use App\Domain\Jobs\Actions\WithdrawJobApplication;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobActions extends Component
{
    #[Locked]
    public int $jobId;

    public string $coverLetter = '';

    public function mount(Job $job): void
    {
        $job = Job::query()->findOrFail($job->id);
        $this->jobId = $job->id;
        Gate::authorize('view', $job);
    }

    public function save(SaveJobForLater $saveJobForLater): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $saved = $saveJobForLater->toggle($this->job(), $user);
        session()->flash('status', $saved ? 'Job saved to your list.' : 'Job removed from your saved list.');
    }

    public function apply(ApplyToJob $applyToJob): ?RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $this->validate(['coverLetter' => ['nullable', 'string', 'max:5000']]);
        $applyToJob->execute($this->job(), $user, $this->coverLetter);
        $this->coverLetter = '';

        return redirect()->route('candidate.applications.index')->with('status', 'Application submitted.');
    }

    public function withdraw(WithdrawJobApplication $withdrawJobApplication): void
    {
        $user = Auth::user();
        abort_if($user === null, 403);
        $application = $user->jobApplications()->where('job_id', $this->jobId)->firstOrFail();
        $withdrawJobApplication->execute($application, $user);
        session()->flash('status', 'Application withdrawn.');
    }

    public function render(): View
    {
        $job = $this->job();
        Gate::authorize('view', $job);
        $user = Auth::user();
        $application = $user?->jobApplications()->where('job_id', $job->id)->first();

        return view('livewire.jobs.job-actions', [
            'job' => $job,
            'application' => $application,
            'canApply' => $user !== null && Gate::allows('apply', $job),
            'canSave' => $user !== null && Gate::allows('save', $job),
            'isSaved' => $user?->savedJobs()->whereKey($job->id)->exists() ?? false,
        ]);
    }

    private function job(): Job
    {
        return Job::query()->with('company')->findOrFail($this->jobId);
    }
}
