<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Domain\Jobs\Actions\ChangeJobStatus;
use App\Domain\Jobs\Actions\DuplicateJob;
use App\Domain\Jobs\Actions\SaveJob;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Enums\LocationType;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageJob extends Component
{
    #[Locked]
    public ?int $jobId = null;

    public string $companyId = '';

    public string $title = '';

    public string $description = '';

    public string $employmentType = 'full_time';

    public string $workplaceType = 'hybrid';

    public string $experienceMin = '';

    public string $experienceMax = '';

    public string $salaryMin = '';

    public string $salaryMax = '';

    public string $currency = '';

    public string $locationId = '';

    public string $itParkId = '';

    public string $responsibilitiesInput = '';

    public string $requirementsInput = '';

    public string $benefitsInput = '';

    public string $skillsInput = '';

    public string $applicationDeadline = '';

    public string $visibility = 'public';

    public function mount(?Job $job = null): void
    {
        if ($job === null) {
            Gate::authorize('create', Job::class);

            return;
        }

        Gate::authorize('manage', $job);
        $job->load(['skills']);
        $this->jobId = $job->id;
        $this->companyId = (string) $job->company_id;
        $this->title = $job->title;
        $this->description = $job->description;
        $this->employmentType = $job->employment_type->value;
        $this->workplaceType = $job->workplace_type->value;
        $this->experienceMin = $job->experience_min === null ? '' : (string) $job->experience_min;
        $this->experienceMax = $job->experience_max === null ? '' : (string) $job->experience_max;
        $this->salaryMin = $job->salary_min === null ? '' : (string) $job->salary_min;
        $this->salaryMax = $job->salary_max === null ? '' : (string) $job->salary_max;
        $this->currency = $job->currency ?? '';
        $this->locationId = $job->location_id === null ? '' : (string) $job->location_id;
        $this->itParkId = $job->it_park_id === null ? '' : (string) $job->it_park_id;
        $this->responsibilitiesInput = implode("\n", $job->responsibilities ?? []);
        $this->requirementsInput = implode("\n", $job->requirements ?? []);
        $this->benefitsInput = implode("\n", $job->benefits ?? []);
        $this->skillsInput = $job->skills->pluck('name')->implode(', ');
        $this->applicationDeadline = $job->application_deadline?->format('Y-m-d') ?? '';
        $this->visibility = $job->visibility->value;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:50', 'max:30000'],
            'employmentType' => ['required', Rule::enum(EmploymentType::class)],
            'workplaceType' => ['required', Rule::enum(WorkplaceType::class)],
            'experienceMin' => ['nullable', 'integer', 'between:0,60'],
            'experienceMax' => ['nullable', 'integer', 'between:0,60'],
            'salaryMin' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'salaryMax' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['nullable', 'required_with:salaryMin,salaryMax', 'regex:/^[A-Za-z]{3}$/'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'itParkId' => ['nullable', 'integer', 'exists:locations,id'],
            'responsibilitiesInput' => ['nullable', 'string', 'max:5000'],
            'requirementsInput' => ['nullable', 'string', 'max:5000'],
            'benefitsInput' => ['nullable', 'string', 'max:3000'],
            'skillsInput' => ['nullable', 'string', 'max:1500'],
            'applicationDeadline' => ['nullable', 'date'],
            'visibility' => ['required', Rule::enum(JobVisibility::class)],
        ];
    }

    public function save(SaveJob $saveJob): RedirectResponse
    {
        $job = $saveJob->execute(Auth::user(), $this->company(), $this->validatedAttributes(), $this->currentJob());
        $this->jobId = $job->id;
        session()->flash('status', 'Job details saved.');

        return redirect()->route('employer.jobs.edit', $job);
    }

    public function publish(SaveJob $saveJob, ChangeJobStatus $changeJobStatus): RedirectResponse
    {
        $job = $saveJob->execute(Auth::user(), $this->company(), $this->validatedAttributes(), $this->currentJob());
        $job = $changeJobStatus->execute($job, Auth::user(), JobStatus::Published);
        session()->flash('status', 'Job published.');

        return redirect()->route('jobs.show', $job->slug);
    }

    public function pause(ChangeJobStatus $changeJobStatus): void
    {
        $changeJobStatus->execute($this->requiredJob(), Auth::user(), JobStatus::Paused);
        session()->flash('status', 'Job paused.');
    }

    public function resume(ChangeJobStatus $changeJobStatus): void
    {
        $changeJobStatus->execute($this->requiredJob(), Auth::user(), JobStatus::Published);
        session()->flash('status', 'Job is live again.');
    }

    public function close(ChangeJobStatus $changeJobStatus): void
    {
        $changeJobStatus->execute($this->requiredJob(), Auth::user(), JobStatus::Closed);
        session()->flash('status', 'Job closed. Existing applications remain available.');
    }

    public function duplicate(DuplicateJob $duplicateJob): RedirectResponse
    {
        $job = $duplicateJob->execute($this->requiredJob(), Auth::user());

        return redirect()->route('employer.jobs.edit', $job)->with('status', 'Draft copy created.');
    }

    public function render(): View
    {
        $job = $this->currentJob();
        if ($job !== null) {
            Gate::authorize('manage', $job);
        }

        return view('livewire.jobs.manage-job', [
            'job' => $job?->load(['company', 'skills'])->loadCount('applications'),
            'companies' => $this->manageableCompanies(),
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'parks' => Location::query()->ofType(LocationType::ItPark)->with('parent')->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::cases(),
            'workplaceTypes' => WorkplaceType::cases(),
            'visibilities' => JobVisibility::cases(),
        ])->extends('layouts.livewire', ['title' => $this->jobId === null ? 'Create a job · M Talent' : 'Manage job · M Talent']);
    }

    /** @return array<string, mixed> */
    private function validatedAttributes(): array
    {
        $data = $this->validate();
        if ($data['experienceMin'] !== '' && $data['experienceMax'] !== '' && (int) $data['experienceMin'] > (int) $data['experienceMax']) {
            throw ValidationException::withMessages(['experienceMax' => 'Maximum experience must be at least the minimum.']);
        }
        if ($data['salaryMin'] !== '' && $data['salaryMax'] !== '' && (float) $data['salaryMin'] > (float) $data['salaryMax']) {
            throw ValidationException::withMessages(['salaryMax' => 'Maximum salary must be at least the minimum.']);
        }

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'employment_type' => $data['employmentType'],
            'workplace_type' => $data['workplaceType'],
            'experience_min' => $data['experienceMin'] ?: null,
            'experience_max' => $data['experienceMax'] ?: null,
            'salary_min' => $data['salaryMin'] ?: null,
            'salary_max' => $data['salaryMax'] ?: null,
            'currency' => $data['currency'] ?: null,
            'location_id' => $data['locationId'] ?: null,
            'it_park_id' => $data['itParkId'] ?: null,
            'responsibilities' => $this->parseList($data['responsibilitiesInput'] ?? ''),
            'requirements' => $this->parseList($data['requirementsInput'] ?? ''),
            'benefits' => $this->parseList($data['benefitsInput'] ?? ''),
            'skills' => $this->parseList(str_replace(',', "\n", $data['skillsInput'] ?? ''), 80),
            'application_deadline' => $data['applicationDeadline'] ?: null,
            'visibility' => $data['visibility'],
        ];
    }

    /** @return list<string> */
    private function parseList(string $input, int $maxLength = 300): array
    {
        $items = preg_split('/[\r\n]+/', $input) ?: [];

        return array_slice(array_unique(array_filter(array_map(fn (string $item): string => mb_substr(trim($item), 0, $maxLength), $items))), 0, 30);
    }

    private function company(): Company
    {
        if (! ctype_digit($this->companyId)) {
            throw ValidationException::withMessages(['companyId' => 'Choose a company for this job.']);
        }

        $company = Company::query()->findOrFail($this->companyId);
        Gate::authorize('manageJobs', $company);

        return $company;
    }

    private function currentJob(): ?Job
    {
        return $this->jobId === null ? null : Job::query()->findOrFail($this->jobId);
    }

    private function requiredJob(): Job
    {
        $job = $this->currentJob() ?? abort(404);
        Gate::authorize('manage', $job);

        return $job;
    }

    private function manageableCompanies(): Collection
    {
        $user = Auth::user();

        return Company::query()->where(function ($query) use ($user): void {
            $query->where('owner_id', $user->id)->orWhereHas('memberships', fn ($membership) => $membership
                ->where('user_id', $user->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter']));
        })->orderBy('name')->get();
    }
}
