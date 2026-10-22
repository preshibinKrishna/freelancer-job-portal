<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Freelancers\Actions\UpdateFreelancerVerification;
use App\Enums\FreelancerVerificationStatus;
use App\Models\FreelancerProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ReviewFreelancerVerificationRequests extends Component
{
    public function decide(string $profileId, string $decision, UpdateFreelancerVerification $update): void
    {
        abort_unless(in_array($decision, [FreelancerVerificationStatus::Verified->value, FreelancerVerificationStatus::Rejected->value], true), 422);
        $profile = FreelancerProfile::query()->where('public_id', $profileId)->where('verification_status', FreelancerVerificationStatus::Pending)->firstOrFail();
        $update->review($profile, Auth::user(), FreelancerVerificationStatus::from($decision));
        session()->flash('status', 'Freelancer verification decision recorded.');
    }

    public function render(): View
    {
        Gate::authorize('verify', FreelancerProfile::class);

        return view('livewire.admin.review-freelancer-verification-requests', [
            'profiles' => FreelancerProfile::query()->where('verification_status', FreelancerVerificationStatus::Pending)->with('talentProfile.user')->oldest()->get(),
        ])->extends('layouts.livewire', ['title' => 'Freelancer verification · M Talent']);
    }
}
