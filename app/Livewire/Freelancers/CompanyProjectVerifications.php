<?php

declare(strict_types=1);

namespace App\Livewire\Freelancers;

use App\Domain\Freelancers\Actions\VerifyFreelancerProject;
use App\Enums\FreelancerProjectVerificationStatus;
use App\Models\FreelancerProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class CompanyProjectVerifications extends Component
{
    public function decide(string $projectId, string $decision, VerifyFreelancerProject $review): void
    {
        abort_unless(in_array($decision, [FreelancerProjectVerificationStatus::Verified->value, FreelancerProjectVerificationStatus::Rejected->value], true), 422);
        $project = $this->visibleProjects()->where('public_id', $projectId)->firstOrFail();
        $review->execute($project, Auth::user(), FreelancerProjectVerificationStatus::from($decision));
        session()->flash('status', 'Project confirmation decision recorded.');
    }

    public function render(): View
    {
        $actor = Auth::user();

        return view('livewire.freelancers.company-project-verifications', [
            'projects' => $this->visibleProjects()->with(['freelancerProfile.talentProfile.user', 'category'])->latest()->get(),
        ])->extends('layouts.livewire', ['title' => 'Project confirmations · M Talent']);
    }

    private function visibleProjects(): Builder
    {
        $actorId = Auth::id();

        return FreelancerProject::query()
            ->where('verification_status', FreelancerProjectVerificationStatus::Pending)
            ->whereHas('requestedCompany', fn ($company) => $company->where('owner_id', $actorId)->orWhereHas('memberships', fn ($members) => $members->where('user_id', $actorId)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])));
    }
}
