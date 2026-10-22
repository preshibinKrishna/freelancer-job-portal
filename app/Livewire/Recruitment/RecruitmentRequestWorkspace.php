<?php

declare(strict_types=1);

namespace App\Livewire\Recruitment;

use App\Domain\Recruitment\Actions\AddCandidateToPipeline;
use App\Domain\Recruitment\Actions\ClaimRecruitmentRequest;
use App\Domain\Recruitment\Actions\MoveCandidatePipelineStage;
use App\Domain\Recruitment\Actions\ScheduleRecruitmentInterview;
use App\Domain\Recruitment\Actions\UpdateRecruitmentInterview;
use App\Enums\CandidatePipelineStage;
use App\Enums\RecruitmentInterviewStatus;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class RecruitmentRequestWorkspace extends Component
{
    public RecruitmentRequest $recruitmentRequest;

    public string $candidateSearch = '';

    public string $candidatePublicId = '';

    public string $interviewEntryId = '';

    public string $interviewTitle = 'Interview';

    public string $interviewStartsAt = '';

    public string $interviewEndsAt = '';

    public string $interviewLocation = '';

    public string $interviewNotes = '';

    public function mount(RecruitmentRequest $recruitmentRequest): void
    {
        Gate::authorize('view', $recruitmentRequest);
        $this->recruitmentRequest = $recruitmentRequest->load(['company', 'creator', 'assignedRecruiter', 'skills', 'location.parent']);
    }

    public function claim(ClaimRecruitmentRequest $claim): void
    {
        $this->recruitmentRequest = $claim->execute($this->recruitmentRequest, Auth::user())->load(['company', 'assignedRecruiter', 'skills']);
        session()->flash('status', 'Requisition assigned to you.');
    }

    public function addCandidate(AddCandidateToPipeline $add): void
    {
        Gate::authorize('managePipeline', $this->recruitmentRequest);
        $data = $this->validate(['candidatePublicId' => ['required', 'string', 'max:26']]);
        $candidate = User::query()->where('public_id', $data['candidatePublicId'])->whereHas('profile', fn ($query) => $query->where('visibility', 'public'))->firstOrFail();
        $add->execute($this->recruitmentRequest, $candidate, Auth::user());
        $this->candidatePublicId = '';
        $this->candidateSearch = '';
        session()->flash('status', 'Candidate added to the New stage.');
    }

    public function moveStage(string $entryId, string $to, MoveCandidatePipelineStage $move): void
    {
        $stage = CandidatePipelineStage::tryFrom($to);
        abort_if($stage === null, 422);
        $entry = $this->entry($entryId);
        $move->execute($entry, Auth::user(), $stage);
        $this->recruitmentRequest->refresh();
        session()->flash('status', 'Candidate pipeline updated.');
    }

    public function scheduleInterview(string $entryId, ScheduleRecruitmentInterview $schedule): void
    {
        $entry = $this->entry($entryId);
        $data = $this->validate([
            'interviewTitle' => ['required', 'string', 'max:180'],
            'interviewStartsAt' => ['required', 'date', 'after:now'],
            'interviewEndsAt' => ['required', 'date', 'after:interviewStartsAt'],
            'interviewLocation' => ['nullable', 'string', 'max:500'],
            'interviewNotes' => ['nullable', 'string', 'max:4000'],
        ]);
        $schedule->execute($entry, Auth::user(), [
            'title' => $data['interviewTitle'], 'starts_at' => $data['interviewStartsAt'], 'ends_at' => $data['interviewEndsAt'],
            'location_or_link' => $data['interviewLocation'] ?: null, 'private_notes' => $data['interviewNotes'] ?: null,
        ]);
        $this->interviewEntryId = '';
        $this->interviewStartsAt = '';
        $this->interviewEndsAt = '';
        session()->flash('status', 'Interview scheduled.');
    }

    public function updateInterview(string $interviewId, string $status, UpdateRecruitmentInterview $update): void
    {
        $next = RecruitmentInterviewStatus::tryFrom($status);
        abort_if($next === null, 422);
        $interview = RecruitmentInterview::query()->where('public_id', $interviewId)->firstOrFail();
        $update->execute($interview, Auth::user(), $next);
        session()->flash('status', 'Interview updated.');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->recruitmentRequest);
        $canRecruit = Gate::allows('managePipeline', $this->recruitmentRequest);
        $canClientReview = Gate::allows('clientReview', $this->recruitmentRequest);
        $entries = $this->recruitmentRequest->pipelineEntries()->with(['candidate.profile.skills', 'interviews'])
            ->latest('created_at')->get();
        $candidateMatches = $canRecruit && mb_strlen(trim($this->candidateSearch)) >= 2
            ? User::query()->where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('name', 'candidate'))
                ->whereHas('profile', fn ($query) => $query->where('visibility', 'public')
                    ->where(fn ($profile) => $profile->where('headline', 'like', '%'.trim($this->candidateSearch).'%')->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.trim($this->candidateSearch).'%'))))
                ->with(['profile.skills'])->limit(8)->get()
            : collect();

        return view('livewire.recruitment.recruitment-request-workspace', [
            'canRecruit' => $canRecruit,
            'canClientReview' => $canClientReview,
            'pipeline' => $entries->groupBy(fn (RecruitmentPipelineEntry $entry): string => $entry->stage->value),
            'stages' => CandidatePipelineStage::cases(),
            'candidateMatches' => $candidateMatches,
            'upcomingInterviews' => RecruitmentInterview::query()->whereHas('pipelineEntry', fn ($query) => $query->where('recruitment_request_id', $this->recruitmentRequest->id))
                ->with(['pipelineEntry.candidate'])->where('status', 'scheduled')->orderBy('starts_at')->limit(20)->get(),
            'activities' => $this->recruitmentRequest->activities()->with(['actor', 'pipelineEntry.candidate'])->latest('created_at')->limit(15)->get(),
        ])->extends('layouts.livewire', ['title' => $this->recruitmentRequest->position.' · Recruitment workspace']);
    }

    private function entry(string $publicId): RecruitmentPipelineEntry
    {
        return $this->recruitmentRequest->pipelineEntries()->where('public_id', $publicId)->firstOrFail();
    }
}
