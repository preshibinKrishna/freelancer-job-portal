<?php

declare(strict_types=1);

namespace App\Livewire\Recruitment;

use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class EmployerRecruitmentRequests extends Component
{
    public function render(): View
    {
        $actor = Auth::user();
        $requests = RecruitmentRequest::query()->with(['company', 'assignedRecruiter', 'skills'])->withCount('pipelineEntries')
            ->whereHas('company', fn ($company) => $company->where('owner_id', $actor->id)
                ->orWhereHas('memberships', fn ($member) => $member->where('user_id', $actor->id)->where('status', 'active')->whereIn('role', ['owner', 'admin', 'recruiter'])))
            ->latest('submitted_at')->get();

        return view('livewire.recruitment.employer-recruitment-requests', ['requests' => $requests])
            ->extends('layouts.livewire', ['title' => 'Recruitment requests · M Talent']);
    }
}
