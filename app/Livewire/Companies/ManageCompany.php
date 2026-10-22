<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Domain\Companies\Actions\SaveCompanyProfile;
use App\Domain\Companies\Actions\SetCompanyMember;
use App\Domain\Companies\Actions\SubmitCompanyVerification;
use App\Enums\CompanyMemberRole;
use App\Enums\CompanySize;
use App\Enums\LocationType;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class ManageCompany extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $companyId = null;

    public string $name = '';

    public string $industry = '';

    public string $description = '';

    public string $website = '';

    public string $companySize = '';

    public string $foundedYear = '';

    public ?int $headquartersLocationId = null;

    public ?int $itParkId = null;

    /** @var array<int, int|string> */
    public array $officeLocationIds = [];

    /** @var array<string, string> */
    public array $socialLinks = ['linkedin' => '', 'github' => '', 'x' => '', 'youtube' => '', 'instagram' => ''];

    public string $benefitsInput = '';

    public string $technologyStackInput = '';

    public string $companyCulture = '';

    public string $contactName = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public bool $published = false;

    public ?TemporaryUploadedFile $logo = null;

    public ?TemporaryUploadedFile $coverImage = null;

    public string $memberEmail = '';

    public string $memberRole = 'recruiter';

    public string $verificationNote = '';

    public function mount(?Company $company = null): void
    {
        if ($company === null) {
            Gate::authorize('create', Company::class);

            return;
        }

        Gate::authorize('update', $company);
        $company->load(['officeLocations', 'memberships.user', 'verificationSubmissions' => fn ($query) => $query->latest()]);
        $this->companyId = $company->id;
        $this->name = $company->name;
        $this->industry = $company->industry ?? '';
        $this->description = $company->description ?? '';
        $this->website = $company->website ?? '';
        $this->companySize = $company->company_size === null ? '' : $company->company_size->value;
        $this->foundedYear = $company->founded_year ? (string) $company->founded_year : '';
        $this->headquartersLocationId = $company->headquarters_location_id;
        $this->itParkId = $company->it_park_id;
        $this->officeLocationIds = $company->officeLocations->modelKeys();
        $this->socialLinks = array_merge($this->socialLinks, $company->social_links ?? []);
        $this->benefitsInput = implode(', ', $company->benefits ?? []);
        $this->technologyStackInput = implode(', ', $company->technology_stack ?? []);
        $this->companyCulture = $company->company_culture ?? '';
        $this->contactName = $company->contact_name ?? '';
        $this->contactEmail = $company->contact_email ?? '';
        $this->contactPhone = $company->contact_phone ?? '';
        $this->published = $company->isPublished();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'industry' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'companySize' => ['nullable', Rule::enum(CompanySize::class)],
            'foundedYear' => ['nullable', 'integer', 'between:1800,'.now()->year],
            'headquartersLocationId' => ['nullable', 'integer', 'exists:locations,id'],
            'itParkId' => ['nullable', 'integer', 'exists:locations,id'],
            'officeLocationIds' => ['array', 'max:30'],
            'officeLocationIds.*' => ['integer', 'distinct', 'exists:locations,id'],
            'socialLinks' => ['array:linkedin,github,x,youtube,instagram'],
            'socialLinks.*' => ['nullable', 'url', 'max:255'],
            'benefitsInput' => ['nullable', 'string', 'max:1500'],
            'technologyStackInput' => ['nullable', 'string', 'max:1500'],
            'companyCulture' => ['nullable', 'string', 'max:3000'],
            'contactName' => ['nullable', 'string', 'max:160'],
            'contactEmail' => ['nullable', 'email', 'max:254'],
            'contactPhone' => ['nullable', 'string', 'max:40'],
            'published' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'coverImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'memberEmail' => ['nullable', 'email', 'max:254'],
            'memberRole' => ['required', Rule::in([CompanyMemberRole::Admin->value, CompanyMemberRole::Recruiter->value, CompanyMemberRole::Member->value])],
            'verificationNote' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(SaveCompanyProfile $saveCompanyProfile): RedirectResponse
    {
        $company = $this->currentCompany();
        if ($company === null) {
            Gate::authorize('create', Company::class);
        } else {
            Gate::authorize('update', $company);
        }

        $validated = $this->validateOnlyProfile();
        $newPaths = [];
        $oldPaths = [$company?->logo_path, $company?->cover_image_path];

        if ($this->logo !== null) {
            $directory = 'companies/'.($company === null ? 'pending/'.auth()->user()->public_id.'/'.Str::ulid() : $company->public_id);
            $validated['logo_path'] = $newPaths[] = $this->logo->storePublicly($directory, 'public');
        } else {
            $validated['logo_path'] = $company?->logo_path;
        }
        if ($this->coverImage !== null) {
            $directory = 'companies/'.($company === null ? 'pending/'.auth()->user()->public_id.'/'.Str::ulid() : $company->public_id);
            $validated['cover_image_path'] = $newPaths[] = $this->coverImage->storePublicly($directory, 'public');
        } else {
            $validated['cover_image_path'] = $company?->cover_image_path;
        }

        try {
            $saved = $company === null
                ? $saveCompanyProfile->create(auth()->user(), $validated)
                : $saveCompanyProfile->update($company, auth()->user(), $validated);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_filter($oldPaths, fn (?string $path): bool => $path !== null && ! in_array($path, $newPaths, true))));
        $this->companyId = $saved->id;
        $this->logo = null;
        $this->coverImage = null;
        session()->flash('status', 'Company profile saved.');

        return redirect()->route('employer.companies.edit', $saved);
    }

    public function addMember(SetCompanyMember $setCompanyMember): void
    {
        $company = $this->requiredCompany();
        Gate::authorize('manageMembers', $company);
        $this->validateOnly('memberEmail');
        $this->validateOnly('memberRole');
        $setCompanyMember->addByEmail($company, auth()->user(), $this->memberEmail, CompanyMemberRole::from($this->memberRole));
        $this->memberEmail = '';
        session()->flash('status', 'Team member added.');
    }

    public function removeMember(int $userId, SetCompanyMember $setCompanyMember): void
    {
        $company = $this->requiredCompany();
        Gate::authorize('manageMembers', $company);
        $setCompanyMember->remove($company, auth()->user(), $userId);
        session()->flash('status', 'Team member removed.');
    }

    public function submitVerification(SubmitCompanyVerification $submitCompanyVerification): void
    {
        $company = $this->requiredCompany();
        Gate::authorize('submitVerification', $company);
        $this->validate(['verificationNote' => ['required', 'string', 'min:20', 'max:2000']]);
        $submitCompanyVerification->execute($company, auth()->user(), $this->verificationNote);
        $this->verificationNote = '';
        session()->flash('status', 'Verification request submitted for review.');
    }

    public function render(): View
    {
        $company = $this->currentCompany();
        if ($company !== null) {
            Gate::authorize('view', $company);
        }

        return view('livewire.companies.manage-company', [
            'company' => $company?->load(['memberships.user', 'verificationSubmissions' => fn ($query) => $query->latest()]),
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area, LocationType::ItPark])->with('parent')->orderBy('name')->get(),
            'parks' => Location::query()->ofType(LocationType::ItPark)->orderBy('name')->get(),
            'sizes' => CompanySize::cases(),
            'memberRoles' => [CompanyMemberRole::Admin, CompanyMemberRole::Recruiter, CompanyMemberRole::Member],
        ])->extends('layouts.livewire', ['title' => $this->companyId ? 'Manage company · M Talent' : 'Create company · M Talent']);
    }

    /** @return array<string, mixed> */
    private function validateOnlyProfile(): array
    {
        $validated = $this->validate();

        return [
            'name' => $validated['name'],
            'industry' => $validated['industry'],
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'company_size' => $validated['companySize'] ?: null,
            'founded_year' => $validated['foundedYear'] ?: null,
            'headquarters_location_id' => $validated['headquartersLocationId'] ?: null,
            'it_park_id' => $validated['itParkId'] ?: null,
            'office_location_ids' => array_map('intval', $validated['officeLocationIds'] ?? []),
            'social_links' => $validated['socialLinks'],
            'benefits' => $this->parseList($validated['benefitsInput'] ?? ''),
            'technology_stack' => $this->parseList($validated['technologyStackInput'] ?? ''),
            'company_culture' => $validated['companyCulture'] ?? null,
            'contact_name' => $validated['contactName'] ?? null,
            'contact_email' => $validated['contactEmail'] ?? null,
            'contact_phone' => $validated['contactPhone'] ?? null,
            'published' => (bool) ($validated['published'] ?? false),
        ];
    }

    /** @return list<string> */
    private function parseList(string $input): array
    {
        $items = preg_split('/[,\n]+/', $input) ?: [];

        return array_slice(array_unique(array_filter(array_map(fn (string $item): string => mb_substr(trim($item), 0, 64), $items))), 0, 20);
    }

    private function currentCompany(): ?Company
    {
        if ($this->companyId === null) {
            return null;
        }

        return Company::query()->with('officeLocations')->findOrFail($this->companyId);
    }

    private function requiredCompany(): Company
    {
        return $this->currentCompany() ?? abort(404);
    }
}
