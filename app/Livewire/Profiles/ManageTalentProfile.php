<?php

declare(strict_types=1);

namespace App\Livewire\Profiles;

use App\Domain\Profiles\Actions\DeleteProfileResume;
use App\Domain\Profiles\Actions\SaveTalentProfile;
use App\Domain\Profiles\Actions\StoreProfileAsset;
use App\Domain\Profiles\Services\ProfileCompleteness;
use App\Enums\ProfileAvailability;
use App\Enums\WorkplaceType;
use App\Models\Location;
use App\Models\ProfileResume;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageTalentProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $headline = '';

    public string $bio = '';

    public string $locationId = '';

    public string $visibility = 'private';

    public string $availability = '';

    public string $experienceYears = '';

    public string $noticePeriodDays = '';

    public string $expectedSalary = '';

    public string $expectedSalaryCurrency = '';

    public string $linkedinUrl = '';

    public string $githubUrl = '';

    public string $skillsInput = '';

    /** @var list<string> */
    public array $preferredWorkTypes = [];

    /** @var list<string> */
    public array $preferredLocationIds = [];

    /** @var list<array<string, string|bool>> */
    public array $experiences = [];

    /** @var list<array<string, string>> */
    public array $educations = [];

    /** @var list<array<string, string>> */
    public array $certifications = [];

    /** @var list<array<string, string>> */
    public array $languages = [];

    /** @var list<array<string, string>> */
    public array $portfolioLinks = [];

    public mixed $photoUpload = null;

    public mixed $resumeUpload = null;

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile()->with(['skills', 'preferredLocations', 'experiences', 'educations', 'certifications', 'languages', 'portfolioLinks', 'resumes'])->first();
        Gate::authorize($profile === null ? 'create' : 'update', $profile ?? UserProfile::class);
        $this->name = $user->name;
        if ($profile === null) {
            $this->experiences = [['title' => '', 'company' => '', 'location' => '', 'description' => '', 'started_at' => '', 'ended_at' => '', 'is_current' => false]];
            $this->educations = [['institution' => '', 'degree' => '', 'field_of_study' => '', 'started_at' => '', 'ended_at' => '']];
            $this->certifications = [['name' => '', 'issuer' => '', 'credential_url' => '', 'issued_at' => '', 'expires_at' => '']];
            $this->languages = [['language' => '', 'proficiency' => '']];
            $this->portfolioLinks = [['title' => '', 'url' => '', 'description' => '']];

            return;
        }

        $this->headline = $profile->headline ?? '';
        $this->bio = $profile->bio ?? '';
        $this->locationId = $profile->location_id === null ? '' : (string) $profile->location_id;
        $this->visibility = $profile->visibility;
        $this->availability = $profile->availability ?? '';
        $this->experienceYears = $profile->experience_years === null ? '' : (string) $profile->experience_years;
        $this->noticePeriodDays = $profile->notice_period_days === null ? '' : (string) $profile->notice_period_days;
        $this->expectedSalary = $profile->expected_salary ?? '';
        $this->expectedSalaryCurrency = $profile->expected_salary_currency ?? '';
        $this->linkedinUrl = $profile->linkedin_url ?? '';
        $this->githubUrl = $profile->github_url ?? '';
        $this->skillsInput = $profile->skills->pluck('name')->implode(', ');
        $this->preferredWorkTypes = $profile->preferred_work_types ?? [];
        $this->preferredLocationIds = $profile->preferredLocations->pluck('id')->map(static fn (int $id): string => (string) $id)->all();
        $this->experiences = $profile->experiences->map(fn ($entry): array => [...$entry->only(['title', 'company', 'location', 'description']), 'started_at' => $entry->started_at?->format('Y-m-d') ?? '', 'ended_at' => $entry->ended_at?->format('Y-m-d') ?? '', 'is_current' => $entry->is_current])->all() ?: [['title' => '', 'company' => '', 'location' => '', 'description' => '', 'started_at' => '', 'ended_at' => '', 'is_current' => false]];
        $this->educations = $profile->educations->map(fn ($entry): array => [...$entry->only(['institution', 'degree', 'field_of_study']), 'started_at' => $entry->started_at?->format('Y-m-d') ?? '', 'ended_at' => $entry->ended_at?->format('Y-m-d') ?? ''])->all() ?: [['institution' => '', 'degree' => '', 'field_of_study' => '', 'started_at' => '', 'ended_at' => '']];
        $this->certifications = $profile->certifications->map(fn ($entry): array => [...$entry->only(['name', 'issuer', 'credential_url']), 'issued_at' => $entry->issued_at?->format('Y-m-d') ?? '', 'expires_at' => $entry->expires_at?->format('Y-m-d') ?? ''])->all() ?: [['name' => '', 'issuer' => '', 'credential_url' => '', 'issued_at' => '', 'expires_at' => '']];
        $this->languages = $profile->languages->map(fn ($entry): array => $entry->only(['language', 'proficiency']))->all() ?: [['language' => '', 'proficiency' => '']];
        $this->portfolioLinks = $profile->portfolioLinks->map(fn ($entry): array => $entry->only(['title', 'url', 'description']))->all() ?: [['title' => '', 'url' => '', 'description' => '']];
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'locationId' => ['nullable', 'integer', 'exists:locations,id'],
            'visibility' => ['required', Rule::in(['private', 'public'])],
            'availability' => ['nullable', Rule::enum(ProfileAvailability::class)],
            'experienceYears' => ['nullable', 'integer', 'between:0,60'],
            'noticePeriodDays' => ['nullable', 'integer', 'between:0,730'],
            'expectedSalary' => ['nullable', 'numeric', 'min:0', 'max:999999999999', 'required_with:expectedSalaryCurrency'],
            'expectedSalaryCurrency' => ['nullable', 'required_with:expectedSalary', 'regex:/^[A-Za-z]{3}$/'],
            'linkedinUrl' => ['nullable', 'url', 'max:255'],
            'githubUrl' => ['nullable', 'url', 'max:255'],
            'skillsInput' => ['nullable', 'string', 'max:2000'],
            'preferredWorkTypes' => ['array', 'max:4'],
            'preferredWorkTypes.*' => [Rule::enum(WorkplaceType::class)],
            'preferredLocationIds' => ['array', 'max:12'],
            'preferredLocationIds.*' => ['integer', 'distinct', 'exists:locations,id'],
            'experiences' => ['array', 'max:20'], 'experiences.*.title' => ['nullable', 'string', 'max:160'], 'experiences.*.company' => ['nullable', 'string', 'max:160'], 'experiences.*.location' => ['nullable', 'string', 'max:160'], 'experiences.*.description' => ['nullable', 'string', 'max:4000'], 'experiences.*.started_at' => ['nullable', 'date'], 'experiences.*.ended_at' => ['nullable', 'date'], 'experiences.*.is_current' => ['boolean'],
            'educations' => ['array', 'max:20'], 'educations.*.institution' => ['nullable', 'string', 'max:180'], 'educations.*.degree' => ['nullable', 'string', 'max:160'], 'educations.*.field_of_study' => ['nullable', 'string', 'max:160'], 'educations.*.started_at' => ['nullable', 'date'], 'educations.*.ended_at' => ['nullable', 'date'],
            'certifications' => ['array', 'max:20'], 'certifications.*.name' => ['nullable', 'string', 'max:180'], 'certifications.*.issuer' => ['nullable', 'string', 'max:180'], 'certifications.*.credential_url' => ['nullable', 'url', 'max:255'], 'certifications.*.issued_at' => ['nullable', 'date'], 'certifications.*.expires_at' => ['nullable', 'date'],
            'languages' => ['array', 'max:20'], 'languages.*.language' => ['nullable', 'string', 'max:80'], 'languages.*.proficiency' => ['nullable', 'string', 'max:32'],
            'portfolioLinks' => ['array', 'max:20'], 'portfolioLinks.*.title' => ['nullable', 'string', 'max:160'], 'portfolioLinks.*.url' => ['nullable', 'url', 'max:255'], 'portfolioLinks.*.description' => ['nullable', 'string', 'max:1000'],
            'photoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'resumeUpload' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    public function save(SaveTalentProfile $save): RedirectResponse
    {
        $data = $this->validate();
        $user = Auth::user();
        $profile = $user->profile;
        Gate::authorize($profile === null ? 'create' : 'update', $profile ?? UserProfile::class);
        $save->execute($user, [
            'name' => $data['name'], 'headline' => $data['headline'], 'bio' => $data['bio'], 'location_id' => $data['locationId'],
            'visibility' => $data['visibility'], 'availability' => $data['availability'], 'experience_years' => $data['experienceYears'],
            'notice_period_days' => $data['noticePeriodDays'], 'expected_salary' => $data['expectedSalary'], 'expected_salary_currency' => $data['expectedSalaryCurrency'],
            'preferred_work_types' => $data['preferredWorkTypes'], 'preferred_location_ids' => $data['preferredLocationIds'],
            'linkedin_url' => $data['linkedinUrl'], 'github_url' => $data['githubUrl'],
            'skills' => $this->parseSkills($data['skillsInput']), 'experiences' => $data['experiences'], 'educations' => $data['educations'],
            'certifications' => $data['certifications'], 'languages' => $data['languages'], 'portfolio_links' => $data['portfolioLinks'],
        ]);
        session()->flash('status', 'Your professional profile has been saved.');

        return redirect()->route('candidate.profile.edit');
    }

    public function savePhoto(StoreProfileAsset $store): void
    {
        $this->validateOnly('photoUpload');
        $user = Auth::user();
        Gate::authorize($user->profile ? 'update' : 'create', $user->profile ?? UserProfile::class);
        if ($this->photoUpload !== null) {
            $store->photo($user, $this->photoUpload);
            $this->photoUpload = null;
            session()->flash('status', 'Profile photo updated.');
        }
    }

    public function uploadResume(StoreProfileAsset $store): void
    {
        $this->validateOnly('resumeUpload');
        $user = Auth::user();
        Gate::authorize($user->profile ? 'update' : 'create', $user->profile ?? UserProfile::class);
        if ($this->resumeUpload !== null) {
            $store->resume($user, $this->resumeUpload);
            $this->resumeUpload = null;
            session()->flash('status', 'Resume uploaded privately. Only you can access it until it has been security-scanned.');
        }
    }

    public function removeResume(string $publicId, DeleteProfileResume $delete): void
    {
        $user = Auth::user();
        $resume = ProfileResume::query()
            ->whereHas('userProfile', static fn ($query) => $query->where('user_id', $user->id))
            ->where('public_id', $publicId)
            ->firstOrFail();
        $delete->execute($resume, $user);
        session()->flash('status', 'Resume deleted from your private profile storage.');
    }

    public function addRow(string $collection): void
    {
        $rows = [
            'experiences' => ['title' => '', 'company' => '', 'location' => '', 'description' => '', 'started_at' => '', 'ended_at' => '', 'is_current' => false],
            'educations' => ['institution' => '', 'degree' => '', 'field_of_study' => '', 'started_at' => '', 'ended_at' => ''],
            'certifications' => ['name' => '', 'issuer' => '', 'credential_url' => '', 'issued_at' => '', 'expires_at' => ''],
            'languages' => ['language' => '', 'proficiency' => ''],
            'portfolioLinks' => ['title' => '', 'url' => '', 'description' => ''],
        ];
        abort_unless(isset($rows[$collection]), 404);
        $this->{$collection}[] = $rows[$collection];
    }

    public function removeRow(string $collection, int $index): void
    {
        abort_unless(in_array($collection, ['experiences', 'educations', 'certifications', 'languages', 'portfolioLinks'], true), 404);
        unset($this->{$collection}[$index]);
        $this->{$collection} = array_values($this->{$collection});
    }

    public function render(ProfileCompleteness $completeness): View
    {
        $user = Auth::user();
        $profile = $user->profile()->withCount('skills')->with(['resumes'])->first();
        Gate::authorize($profile === null ? 'create' : 'update', $profile ?? UserProfile::class);

        return view('livewire.profiles.manage-talent-profile', [
            'profile' => $profile,
            'completion' => $completeness->assess($profile),
            'locations' => Location::query()->with('parent')->orderBy('name')->get(),
            'workplaceTypes' => WorkplaceType::cases(),
            'availabilityOptions' => ProfileAvailability::cases(),
        ])->extends('layouts.livewire', ['title' => 'Professional profile · M Talent']);
    }

    /** @return list<string> */
    private function parseSkills(string $input): array
    {
        return collect(explode(',', $input))->map(static fn (string $skill): string => trim($skill))->filter()->unique(static fn (string $skill): string => mb_strtolower($skill))->take(40)->values()->all();
    }
}
