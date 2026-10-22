<?php

declare(strict_types=1);

namespace App\Livewire\Freelancers;

use App\Domain\Freelancers\Actions\EnableFreelancerProfile;
use App\Domain\Freelancers\Actions\SaveFreelancerProfile;
use App\Domain\Freelancers\Actions\SaveFreelancerProject;
use App\Domain\Freelancers\Actions\SaveFreelancerVerificationRequest;
use App\Enums\ProfileAvailability;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\FreelancerProfile;
use App\Models\FreelancerProject;
use App\Models\ProjectCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageFreelancerProfile extends Component
{
    public ?int $freelancerProfileId = null;

    public string $hourlyRate = '';

    public string $projectRate = '';

    public string $currency = '';

    public string $weeklyCapacity = '';

    public string $availability = '';

    public bool $isListed = false;

    /** @var list<string> */
    public array $categoryIds = [];

    /** @var list<array<string, string>> */
    public array $projects = [];

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile?->freelancerProfile()->with(['categories', 'projects'])->first();
        if ($profile === null) {
            Gate::authorize('create', FreelancerProfile::class);
            $this->projects = [$this->emptyProject()];

            return;
        }

        Gate::authorize('update', $profile);
        $this->freelancerProfileId = $profile->id;
        $this->hourlyRate = $profile->hourly_rate ?? '';
        $this->projectRate = $profile->project_rate ?? '';
        $this->currency = $profile->currency ?? '';
        $this->weeklyCapacity = $profile->weekly_capacity === null ? '' : (string) $profile->weekly_capacity;
        $this->isListed = $profile->is_listed;
        $this->availability = $profile->talentProfile?->availability ?? '';
        $this->categoryIds = $profile->categories->pluck('id')->map(static fn (int $id): string => (string) $id)->all();
        $this->projects = $profile->projects->map(fn (FreelancerProject $project): array => [
            'public_id' => $project->public_id,
            'project_category_id' => (string) $project->project_category_id,
            'title' => $project->title,
            'summary' => $project->summary,
            'role' => $project->role ?? '',
            'project_url' => $project->project_url ?? '',
            'completed_at' => $project->completed_at?->format('Y-m-d') ?? '',
            'requested_company_id' => $project->requested_company_id === null ? '' : (string) $project->requested_company_id,
        ])->all();
        if ($this->projects === []) {
            $this->projects = [$this->emptyProject()];
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'hourlyRate' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'projectRate' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['nullable', 'required_with:hourlyRate,projectRate', 'regex:/^[A-Za-z]{3}$/'],
            'weeklyCapacity' => ['nullable', 'integer', 'between:1,168'],
            'availability' => ['nullable', Rule::enum(ProfileAvailability::class)],
            'isListed' => ['boolean'],
            'categoryIds' => ['array', 'max:20'],
            'categoryIds.*' => ['integer', 'distinct', 'exists:project_categories,id'],
            'projects' => ['array', 'max:30'],
            'projects.*.public_id' => ['nullable', 'string', 'max:26'],
            'projects.*.project_category_id' => ['nullable', 'integer', 'exists:project_categories,id'],
            'projects.*.title' => ['nullable', 'string', 'max:180'],
            'projects.*.summary' => ['nullable', 'string', 'max:5000'],
            'projects.*.role' => ['nullable', 'string', 'max:120'],
            'projects.*.project_url' => ['nullable', 'url', 'max:255'],
            'projects.*.completed_at' => ['nullable', 'date'],
            'projects.*.requested_company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')->whereNotNull('published_at')],
        ];
    }

    public function save(EnableFreelancerProfile $enable, SaveFreelancerProfile $save): RedirectResponse
    {
        $data = $this->validate();
        $user = Auth::user();
        $profile = $this->freelancerProfile();
        if ($profile === null) {
            $profile = $enable->execute($user);
        }
        $profile = $save->execute($profile, $user, [
            'hourly_rate' => $data['hourlyRate'] !== '' ? $data['hourlyRate'] : null,
            'project_rate' => $data['projectRate'] !== '' ? $data['projectRate'] : null,
            'currency' => $data['currency'] !== '' ? $data['currency'] : null,
            'weekly_capacity' => $data['weeklyCapacity'] !== '' ? (int) $data['weeklyCapacity'] : null,
            'availability' => $data['availability'] !== '' ? $data['availability'] : null,
            'is_listed' => (bool) $data['isListed'],
            'category_ids' => array_map('intval', $data['categoryIds']),
        ]);
        $this->freelancerProfileId = $profile->id;
        session()->flash('status', 'Freelancer profile settings saved.');

        return redirect()->route('freelancer.profile.edit');
    }

    public function saveProject(int $index, SaveFreelancerProject $save, EnableFreelancerProfile $enable): void
    {
        $data = $this->validate([
            "projects.{$index}.public_id" => ['nullable', 'string', 'max:26'],
            "projects.{$index}.project_category_id" => ['required', 'integer', 'exists:project_categories,id'],
            "projects.{$index}.title" => ['required', 'string', 'max:180'],
            "projects.{$index}.summary" => ['required', 'string', 'min:20', 'max:5000'],
            "projects.{$index}.role" => ['nullable', 'string', 'max:120'],
            "projects.{$index}.project_url" => ['nullable', 'url', 'max:255'],
            "projects.{$index}.completed_at" => ['nullable', 'date', "required_with:projects.{$index}.requested_company_id"],
            "projects.{$index}.requested_company_id" => ['nullable', 'integer', Rule::exists('companies', 'id')->whereNotNull('published_at')],
        ]);
        $user = Auth::user();
        $profile = $this->freelancerProfile() ?? $enable->execute($user);
        $projectData = $data['projects'][$index];
        $project = filled($projectData['public_id'] ?? null)
            ? $profile->projects()->where('public_id', $projectData['public_id'])->firstOrFail()
            : null;
        $save->execute($profile, $user, [
            'project_category_id' => (int) $projectData['project_category_id'],
            'title' => $projectData['title'],
            'summary' => $projectData['summary'],
            'role' => $projectData['role'] ?: null,
            'project_url' => $projectData['project_url'] ?: null,
            'completed_at' => $projectData['completed_at'] ?: null,
            'requested_company_id' => $projectData['requested_company_id'] !== '' ? (int) $projectData['requested_company_id'] : null,
        ], $project);
        $this->dispatch('$refresh');
        session()->flash('status', 'Portfolio project saved. Company confirmation is required before it is shown as verified completed work.');
    }

    public function addProject(): void
    {
        $this->projects[] = $this->emptyProject();
    }

    public function requestVerification(SaveFreelancerVerificationRequest $request): void
    {
        $profile = $this->freelancerProfile();
        abort_if($profile === null, 404);
        $request->execute($profile, Auth::user());
        session()->flash('status', 'Your freelancer verification request has been submitted.');
    }

    public function render(): View
    {
        $profile = $this->freelancerProfile()?->load(['talentProfile.user', 'categories', 'projects.category', 'projects.requestedCompany', 'projects.review']);
        if ($profile !== null) {
            Gate::authorize('update', $profile);
        } else {
            Gate::authorize('create', FreelancerProfile::class);
        }

        return view('livewire.freelancers.manage-freelancer-profile', [
            'profile' => $profile,
            'categories' => ProjectCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'availabilityOptions' => ProfileAvailability::cases(),
            'workplaceTypes' => WorkplaceType::cases(),
            'companies' => Company::query()->whereNotNull('published_at')->orderBy('name')->get(['id', 'name']),
            'visibility' => $profile?->talentProfile?->visibility ?? 'private',
        ])->extends('layouts.livewire', ['title' => 'Freelancer portfolio · M Talent']);
    }

    private function freelancerProfile(): ?FreelancerProfile
    {
        return Auth::user()->profile?->freelancerProfile()->first();
    }

    /** @return array<string, string> */
    private function emptyProject(): array
    {
        return ['public_id' => '', 'project_category_id' => '', 'title' => '', 'summary' => '', 'role' => '', 'project_url' => '', 'completed_at' => '', 'requested_company_id' => ''];
    }
}
