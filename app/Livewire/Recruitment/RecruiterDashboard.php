<?php

declare(strict_types=1);

namespace App\Livewire\Recruitment;

use App\Domain\Recruitment\Actions\ClaimRecruitmentRequest;
use App\Models\RecruitmentActivity;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class RecruiterDashboard extends Component
{
    public string $search = '';

    public function mount(): void
    {
        $actor = Auth::user();
        abort_unless($actor->hasRole('recruiter', 'super_admin'), 403);
    }

    public function claim(string $requestId, ClaimRecruitmentRequest $claim): mixed
    {
        $request = RecruitmentRequest::query()->where('public_id', $requestId)->firstOrFail();
        $claim->execute($request, Auth::user());

        return redirect()->route('recruitment.requests.show', $request);
    }

    public function render(): View
    {
        $actor = Auth::user();
        $requests = RecruitmentRequest::query()->with(['company', 'assignedRecruiter', 'skills'])->withCount('pipelineEntries')
            ->where(fn ($query) => $query->whereNull('assigned_recruiter_id')->orWhere('assigned_recruiter_id', $actor->id))
            ->whereIn('status', ['submitted', 'active', 'on_hold', 'filled'])
            ->when($this->search !== '', fn ($query) => $query->where('position', 'like', '%'.$this->search.'%'))
            ->latest('submitted_at')->limit(50)->get();
        $requestIds = $requests->pluck('id');
        $entries = RecruitmentPipelineEntry::query()->whereIn('recruitment_request_id', RecruitmentRequest::query()->where('assigned_recruiter_id', $actor->id)->select('id'));
        $upcomingInterviews = RecruitmentInterview::query()->where('starts_at', '>=', now())->where('status', 'scheduled')
            ->whereHas('pipelineEntry.recruitmentRequest', fn ($query) => $query->where('assigned_recruiter_id', $actor->id))
            ->with(['pipelineEntry.candidate', 'pipelineEntry.recruitmentRequest.company'])->orderBy('starts_at')->limit(8)->get();
        $recentActivity = RecruitmentActivity::query()->whereHas('recruitmentRequest', fn ($query) => $query->where(fn ($scope) => $scope->whereNull('assigned_recruiter_id')->orWhere('assigned_recruiter_id', $actor->id)))
            ->with(['actor', 'recruitmentRequest.company', 'pipelineEntry.candidate'])->latest('created_at')->limit(10)->get();

        return view('livewire.recruitment.recruiter-dashboard', [
            'requests' => $requests,
            'activeRequisitions' => RecruitmentRequest::query()->where('assigned_recruiter_id', $actor->id)->where('status', 'active')->count(),
            'pipelineCount' => $entries->count(),
            'interviewCount' => $upcomingInterviews->count(),
            'placementCount' => $entries->where('stage', 'placed')->count(),
            'pendingActionCount' => RecruitmentRequest::query()->whereNull('assigned_recruiter_id')->where('status', 'submitted')->count()
                + RecruitmentPipelineEntry::query()->where('stage', 'client_review')->whereHas('recruitmentRequest', fn ($query) => $query->where('assigned_recruiter_id', $actor->id))->count(),
            'upcomingInterviews' => $upcomingInterviews,
            'recentActivity' => $recentActivity,
        ])->extends('layouts.livewire', ['title' => 'Recruiter dashboard · M Talent']);
    }
}
