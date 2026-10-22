<?php

declare(strict_types=1);

namespace App\Livewire\Recruitment;

use App\Domain\Recruitment\Actions\CreateRecruitmentRequest as CreateRequestAction;
use App\Enums\LocationType;
use App\Enums\RecruitmentUrgency;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\Location;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageRecruitmentRequest extends Component
{
    public string $companyId = '';

    public string $position = '';

    public string $numberOfOpenings = '1';

    public string $experienceMin = '';

    public string $experienceMax = '';

    public string $salaryMin = '';

    public string $salaryMax = '';

    public string $currency = '';

    public string $locationId = '';

    public string $workplaceType = 'remote';

    public string $noticePeriodDays = '';

    public string $urgency = 'normal';

    public string $description = '';

    public string $skillsInput = '';

    protected function rules(): array
    {
        return [
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'position' => ['required', 'string', 'max:180'],
            'numberOfOpenings' => ['required', 'integer', 'between:1,100'],
            'experienceMin' => ['nullable', 'integer', 'between:0,60'],
            'experienceMax' => ['nullable', 'integer', 'between:0,60'],
            'salaryMin' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'salaryMax' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => [Rule::requiredIf(fn (): bool => $this->salaryMin !== '' || $this->salaryMax !== ''), 'nullable', 'regex:/^[A-Za-z]{3}$/'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'workplaceType' => ['required', Rule::enum(WorkplaceType::class)],
            'noticePeriodDays' => ['nullable', 'integer', 'between:0,365'],
            'urgency' => ['required', Rule::enum(RecruitmentUrgency::class)],
            'description' => ['required', 'string', 'min:50', 'max:15000'],
            'skillsInput' => ['required', 'string', 'max:2000'],
        ];
    }

    public function submit(CreateRequestAction $create): RedirectResponse
    {
        $data = $this->validate();
        $company = Company::query()->findOrFail($data['companyId']);
        Gate::authorize('create', [RecruitmentRequest::class, $company]);
        $request = $create->execute(Auth::user(), $company, [
            'position' => $data['position'], 'number_of_openings' => (int) $data['numberOfOpenings'],
            'experience_min' => $data['experienceMin'] !== '' ? (int) $data['experienceMin'] : null,
            'experience_max' => $data['experienceMax'] !== '' ? (int) $data['experienceMax'] : null,
            'salary_min' => $data['salaryMin'] ?: null, 'salary_max' => $data['salaryMax'] ?: null,
            'currency' => $data['currency'], 'location_id' => $data['locationId'] ?: null,
            'workplace_type' => $data['workplaceType'], 'notice_period_days' => $data['noticePeriodDays'] !== '' ? (int) $data['noticePeriodDays'] : null,
            'urgency' => $data['urgency'], 'description' => $data['description'],
            'skills' => collect(preg_split('/[,\r\n]+/', $data['skillsInput']) ?: [])->map(fn (string $skill): string => trim($skill))->filter()->unique()->take(40)->values()->all(),
        ]);

        return redirect()->route('recruitment.requests.show', $request)->with('status', 'Recruitment assistance request submitted.');
    }

    public function render(): View
    {
        $companies = Company::query()->where(fn (Builder $query) => $query->where('owner_id', Auth::id())->orWhereHas('memberships', fn (Builder $membership) => $membership->where('user_id', Auth::id())->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])))->orderBy('name')->get();
        abort_if($companies->isEmpty(), 403);

        return view('livewire.recruitment.manage-recruitment-request', [
            'companies' => $companies,
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'workplaceTypes' => WorkplaceType::cases(),
            'urgencies' => RecruitmentUrgency::cases(),
        ])->extends('layouts.livewire', ['title' => 'Request recruitment assistance · M Talent']);
    }
}
