<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class JobDashboard extends Component
{
    use WithPagination;

    public string $companyId = '';

    public function updatedCompanyId(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $companies = $this->manageableCompanies();
        $jobs = Job::query()->whereIn('company_id', $companies->modelKeys())->with(['company', 'location', 'itPark'])->withCount('applications')
            ->when($this->companyId !== '' && ctype_digit($this->companyId), fn ($query) => $query->where('company_id', (int) $this->companyId))
            ->latest()->paginate(12);

        return view('livewire.jobs.dashboard', compact('companies', 'jobs'))
            ->extends('layouts.livewire', ['title' => 'Manage jobs · M Talent']);
    }

    private function manageableCompanies(): Collection
    {
        $user = Auth::user();

        return Company::query()->where(function ($query) use ($user): void {
            $query->where('owner_id', $user->id)->orWhereHas('memberships', fn ($membership) => $membership
                ->where('user_id', $user->id)
                ->where('status', CompanyMembershipStatus::Active)
                ->whereIn('role', [CompanyMemberRole::Owner, CompanyMemberRole::Admin, CompanyMemberRole::Recruiter]));
        })->orderBy('name')->get();
    }
}
