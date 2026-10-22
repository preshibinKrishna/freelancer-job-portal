<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Domain\Projects\Actions\ChangeEngagementProjectStatus;
use App\Domain\Projects\Actions\SaveEngagementProject;
use App\Domain\Projects\Actions\StoreProjectAttachment;
use App\Enums\EngagementProjectStatus;
use App\Enums\LocationType;
use App\Enums\ProjectPricingType;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageEngagementProject extends Component
{
    use WithFileUploads;

    public ?string $projectPublicId = null;

    public string $companyId = '';

    public string $title = '';

    public string $description = '';

    public string $budgetMin = '';

    public string $budgetMax = '';

    public string $currency = '';

    public string $pricingType = 'fixed';

    public string $estimatedDurationDays = '';

    public string $experienceMin = '';

    public string $locationId = '';

    public bool $isRemote = false;

    public string $applicationDeadline = '';

    public string $skillsInput = '';

    public mixed $attachmentUpload = null;

    public function mount(?EngagementProject $project = null): void
    {
        if ($project === null) {
            Gate::authorize('create', [EngagementProject::class, $this->manageableCompanies()->firstOrFail()]);

            return;
        }
        Gate::authorize('update', $project);
        $this->projectPublicId = $project->public_id;
        $this->companyId = (string) $project->company_id;
        $this->title = $project->title;
        $this->description = $project->description;
        $this->budgetMin = $project->budget_min ?? '';
        $this->budgetMax = $project->budget_max ?? '';
        $this->currency = $project->currency;
        $this->pricingType = $project->pricing_type->value;
        $this->estimatedDurationDays = $project->estimated_duration_days === null ? '' : (string) $project->estimated_duration_days;
        $this->experienceMin = $project->experience_min === null ? '' : (string) $project->experience_min;
        $this->locationId = $project->location_id === null ? '' : (string) $project->location_id;
        $this->isRemote = $project->is_remote;
        $this->applicationDeadline = $project->application_deadline?->format('Y-m-d') ?? '';
        $this->skillsInput = $project->skills->pluck('name')->implode(', ');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:50', 'max:30000'],
            'budgetMin' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'budgetMax' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['required', 'regex:/^[A-Za-z]{3}$/'],
            'pricingType' => ['required', Rule::enum(ProjectPricingType::class)],
            'estimatedDurationDays' => ['nullable', 'integer', 'between:1,3650'],
            'experienceMin' => ['nullable', 'integer', 'between:0,60'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'isRemote' => ['boolean'],
            'applicationDeadline' => ['nullable', 'date', 'after:today'],
            'skillsInput' => ['nullable', 'string', 'max:2000'],
            'attachmentUpload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ];
    }

    public function save(SaveEngagementProject $save): RedirectResponse
    {
        $data = $this->validatedData();
        $company = Company::query()->findOrFail($data['company_id']);
        $project = $save->execute(Auth::user(), $company, $data, $this->project());
        session()->flash('status', 'Project brief saved as a draft.');

        return redirect()->route('employer.projects.edit', $project);
    }

    public function publish(SaveEngagementProject $save, ChangeEngagementProjectStatus $change): RedirectResponse
    {
        $data = $this->validatedData();
        $company = Company::query()->findOrFail($data['company_id']);
        $project = $save->execute(Auth::user(), $company, $data, $this->project());
        $project = $change->execute($project, Auth::user(), EngagementProjectStatus::Published);

        return redirect()->route('projects.show', $project)->with('status', 'Project published.');
    }

    public function uploadAttachment(StoreProjectAttachment $store): void
    {
        $this->validateOnly('attachmentUpload');
        $project = $this->project();
        abort_if($project === null, 409, 'Save the project draft before adding attachments.');
        $store->execute($project, Auth::user(), $this->attachmentUpload);
        $this->attachmentUpload = null;
        session()->flash('status', 'Private project attachment uploaded.');
    }

    public function render(): View
    {
        return view('livewire.projects.manage-engagement-project', [
            'project' => $this->project()?->load(['company', 'skills', 'attachments']),
            'companies' => $this->manageableCompanies()->get(),
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'pricingTypes' => ProjectPricingType::cases(),
        ])->extends('layouts.livewire', ['title' => $this->projectPublicId === null ? 'Create a freelance project · M Talent' : 'Edit project brief · M Talent']);
    }

    /** @return array<string, mixed> */
    private function validatedData(): array
    {
        $data = $this->validate();
        if ($data['budgetMin'] !== '' && $data['budgetMax'] !== '' && (float) $data['budgetMin'] > (float) $data['budgetMax']) {
            throw ValidationException::withMessages(['budgetMax' => 'Maximum budget must be at least the minimum.']);
        }

        return [
            'company_id' => (int) $data['companyId'],
            'title' => $data['title'], 'description' => $data['description'],
            'budget_min' => $data['budgetMin'] ?: null, 'budget_max' => $data['budgetMax'] ?: null,
            'currency' => strtoupper($data['currency']), 'pricing_type' => $data['pricingType'],
            'estimated_duration_days' => $data['estimatedDurationDays'] !== '' ? (int) $data['estimatedDurationDays'] : null,
            'experience_min' => $data['experienceMin'] !== '' ? (int) $data['experienceMin'] : null,
            'location_id' => $data['locationId'] ?: null, 'is_remote' => (bool) $data['isRemote'],
            'application_deadline' => $data['applicationDeadline'] ?: null,
            'skills' => collect(preg_split('/[,\r\n]+/', $data['skillsInput'] ?? '') ?: [])->map(fn (string $skill) => trim($skill))->filter()->unique()->take(40)->values()->all(),
        ];
    }

    private function project(): ?EngagementProject
    {
        if ($this->projectPublicId === null) {
            return null;
        }
        $project = EngagementProject::query()->with('skills')->where('public_id', $this->projectPublicId)->firstOrFail();
        Gate::authorize('update', $project);

        return $project;
    }

    private function manageableCompanies(): Builder
    {
        $user = Auth::user();

        return Company::query()->where(fn ($query) => $query->where('owner_id', $user->id)->orWhereHas('memberships', fn ($membership) => $membership->where('user_id', $user->id)->where('status', 'active')->whereIn('role', ['admin', 'recruiter'])));
    }
}
