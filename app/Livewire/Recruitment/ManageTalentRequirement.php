<?php

declare(strict_types=1);

namespace App\Livewire\Recruitment;

use App\Domain\Matching\Actions\CreateTalentRequirementRequest;
use App\Models\Company;
use App\Models\TalentRequirementRequest;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageTalentRequirement extends Component
{
    use WithPagination;

    #[Locked]
    public ?string $requestPublicId = null;

    public string $companyId = '';

    public string $category = '';

    public string $solutionType = '';

    /** @var array<string, string> */
    public array $answers = [];

    /** @var list<string> */
    public array $selectedSkills = [];

    public function mount(?TalentRequirementRequest $request = null): void
    {
        if ($request === null) {
            $companies = $this->manageableCompanies();
            abort_if($companies->isEmpty(), 403);
            $this->companyId = (string) $companies->first()->id;

            return;
        }

        Gate::authorize('view', $request);
        $this->requestPublicId = $request->public_id;
    }

    public function updatedCategory(): void
    {
        $this->solutionType = '';
        $this->answers = [];
        $this->selectedSkills = [];
        $this->resetPage();
    }

    public function updatedSolutionType(): void
    {
        $this->answers = [];
        $this->selectedSkills = $this->typeDefinition()['skills'] ?? [];
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'category' => ['required', Rule::in(array_keys(config('talent_requirements.categories', [])))],
            'solutionType' => ['required', Rule::in(array_keys($this->types()))],
            'answers' => ['required', 'array', 'min:'.(count(config('talent_requirements.common_fields', [])) + count($this->typeDefinition()['fields'] ?? []))],
            'answers.*' => ['required', 'string', 'max:100'],
            'selectedSkills' => ['required', 'array', 'min:1', 'max:12'],
            'selectedSkills.*' => ['required', 'string', Rule::in($this->typeDefinition()['skills'] ?? [])],
        ];
    }

    public function submit(CreateTalentRequirementRequest $create): RedirectResponse
    {
        $data = $this->validate();
        $company = Company::query()->findOrFail($data['companyId']);
        $allowedAnswers = array_merge(config('talent_requirements.common_fields', []), $this->typeDefinition()['fields'] ?? []);
        foreach ($allowedAnswers as $key => $field) {
            $value = $data['answers'][$key] ?? null;
            if (! is_string($value) || ! array_key_exists($value, $field['options'])) {
                throw ValidationException::withMessages(['answers.'.$key => 'Choose one of the available options.']);
            }
        }
        if (array_diff(array_keys($data['answers']), array_keys($allowedAnswers)) !== []) {
            throw ValidationException::withMessages(['answers' => 'Refresh the form and choose from the current requirement options.']);
        }

        $request = $create->execute(Auth::user(), $company, $data['category'], $data['solutionType'], $data['answers'], array_values(array_unique($data['selectedSkills'])));

        return redirect()->route('employer.talent-requirements.matches', $request)->with('status', 'Your requirements are saved. Here are public professional profiles relevant to your selections.');
    }

    public function render(): View
    {
        $request = $this->requestPublicId === null ? null : TalentRequirementRequest::query()->with('company')->where('public_id', $this->requestPublicId)->firstOrFail();
        if ($request !== null) {
            Gate::authorize('view', $request);
        }

        $companies = $this->manageableCompanies();
        $profiles = null;
        $resultType = $request === null ? $this->typeDefinition() : (config('talent_requirements.categories.'.$request->category.'.types.'.$request->solution_type) ?? []);
        if ($request !== null) {
            $skills = $request->suggested_skills;
            $profiles = UserProfile::query()
                ->where('visibility', 'public')
                ->whereNotNull('profile_slug')
                ->whereHas('user', fn (Builder $user): Builder => $user->where('status', 'active')->whereNotNull('email_verified_at'))
                ->whereHas('skills', fn (Builder $query): Builder => $query->whereIn('name', $skills))
                ->with(['user', 'primaryLocation.parent', 'skills'])
                ->withCount(['skills as matched_skills_count' => fn (Builder $query): Builder => $query->whereIn('name', $skills)])
                ->orderByDesc('matched_skills_count')
                ->orderByDesc('experience_years')
                ->paginate(12);
        }

        return view('livewire.recruitment.manage-talent-requirement', [
            'companies' => $companies,
            'categories' => config('talent_requirements.categories', []),
            'types' => $this->types(),
            'typeDefinition' => $resultType,
            'commonFields' => config('talent_requirements.common_fields', []),
            'selectedFields' => array_merge(config('talent_requirements.common_fields', []), $resultType['fields'] ?? []),
            'request' => $request,
            'matches' => $profiles,
        ])->extends('layouts.livewire', ['title' => $request === null ? 'Guided talent search · M Talent' : 'Recommended professionals · M Talent']);
    }

    /** @return array<string, array<string, mixed>> */
    private function types(): array
    {
        return config('talent_requirements.categories.'.$this->category.'.types', []);
    }

    /** @return array<string, mixed> */
    private function typeDefinition(): array
    {
        return $this->types()[$this->solutionType] ?? [];
    }

    /** @return Collection<int, Company> */
    private function manageableCompanies(): Collection
    {
        return Company::query()->where(fn (Builder $query) => $query->where('owner_id', Auth::id())->orWhereHas('memberships', fn (Builder $membership) => $membership->where('user_id', Auth::id())->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])))->orderBy('name')->get();
    }
}
