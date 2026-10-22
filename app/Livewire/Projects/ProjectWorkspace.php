<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Domain\Contracts\Actions\CreateContractFromAward;
use App\Domain\Projects\Actions\AwardProjectProposal;
use App\Domain\Projects\Actions\ChangeEngagementProjectStatus;
use App\Domain\Projects\Actions\ReviewProjectProposal;
use App\Domain\Projects\Actions\SendProjectMessage;
use App\Domain\Projects\Actions\SubmitProjectProposal;
use App\Domain\Projects\Actions\ToggleSavedProject;
use App\Domain\Projects\Actions\WithdrawProjectProposal;
use App\Enums\EngagementProjectStatus;
use App\Enums\ProjectProposalStatus;
use App\Models\EngagementProject;
use App\Models\FreelancerProject;
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ProjectWorkspace extends Component
{
    public EngagementProject $project;

    public ?string $activeProposalId = null;

    public string $coverLetter = '';

    public string $proposedPrice = '';

    public string $proposedDurationDays = '';

    public string $messageText = '';

    public string $contractStartDate = '';

    public string $contractEndDate = '';

    public string $contractTerms = '';

    /** @var list<string> */
    public array $portfolioIds = [];

    /** @var list<array{title: string, description: string, amount: string, duration_days: string}> */
    public array $milestones = [];

    public function mount(EngagementProject $project): void
    {
        $this->project = $project;
        abort_unless(Gate::allows('view', $project), 404);
        $this->project->load(['company', 'skills', 'location.parent', 'attachments', 'milestones']);
        $ownProposal = $this->ownProposal();
        $this->activeProposalId = $ownProposal?->public_id;
        $this->contractStartDate = now()->toDateString();
        $this->milestones = [['title' => '', 'description' => '', 'amount' => '', 'duration_days' => '']];
    }

    public function saveProject(ToggleSavedProject $toggle): void
    {
        $saved = $toggle->execute($this->project, Auth::user());
        session()->flash('status', $saved ? 'Project saved to your list.' : 'Project removed from your saved list.');
    }

    public function addMilestone(): void
    {
        abort_if(count($this->milestones) >= 20, 422);
        $this->milestones[] = ['title' => '', 'description' => '', 'amount' => '', 'duration_days' => ''];
    }

    public function removeMilestone(int $index): void
    {
        unset($this->milestones[$index]);
        $this->milestones = array_values($this->milestones);
    }

    public function submit(SubmitProjectProposal $submit): void
    {
        $data = $this->validate([
            'coverLetter' => ['required', 'string', 'min:50', 'max:8000'],
            'proposedPrice' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'proposedDurationDays' => ['required', 'integer', 'between:1,3650'],
            'portfolioIds' => ['array', 'max:12'], 'portfolioIds.*' => ['string', 'distinct', 'max:26'],
            'milestones' => ['array', 'max:20'],
            'milestones.*.title' => ['required', 'string', 'max:180'],
            'milestones.*.description' => ['nullable', 'string', 'max:2000'],
            'milestones.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'milestones.*.duration_days' => ['nullable', 'integer', 'between:1,3650'],
        ]);
        $submit->execute($this->project, Auth::user(), [
            'cover_letter' => $data['coverLetter'], 'proposed_price' => $data['proposedPrice'],
            'currency' => $this->project->currency, 'proposed_duration_days' => (int) $data['proposedDurationDays'],
            'portfolio_ids' => $data['portfolioIds'],
            'milestones' => collect($data['milestones'])->map(fn (array $milestone): array => [
                'title' => $milestone['title'], 'description' => $milestone['description'] ?: null,
                'amount' => $milestone['amount'] !== '' ? $milestone['amount'] : null,
                'duration_days' => $milestone['duration_days'] !== '' ? (int) $milestone['duration_days'] : null,
            ])->all(),
        ]);
        $this->project->refresh();
        $this->activeProposalId = $this->ownProposal()?->public_id;
        session()->flash('status', 'Proposal submitted. Keep project communication in this workspace.');
    }

    public function withdraw(WithdrawProjectProposal $withdraw): void
    {
        $proposal = $this->ownProposal();
        abort_if($proposal === null, 404);
        $withdraw->execute($proposal, Auth::user());
        session()->flash('status', 'Your proposal was withdrawn.');
    }

    public function reviewProposal(string $proposalId, string $status, ReviewProjectProposal $review): void
    {
        $proposal = $this->proposal($proposalId);
        $review->execute($proposal, Auth::user(), ProjectProposalStatus::from($status));
        $this->activeProposalId = $proposal->public_id;
        session()->flash('status', 'Proposal review saved.');
    }

    public function award(string $proposalId, AwardProjectProposal $award): void
    {
        $proposal = $this->proposal($proposalId);
        $award->execute($proposal, Auth::user());
        $this->project->refresh()->load('milestones');
        session()->flash('status', 'Project awarded. The proposal milestones are now the project delivery plan.');
    }

    public function createContract(string $proposalId, CreateContractFromAward $create): mixed
    {
        $actor = Auth::user();
        abort_unless($actor !== null, 403);
        $proposal = $this->proposal($proposalId);
        Gate::authorize('review', $proposal);
        $data = $this->validate([
            'contractStartDate' => ['required', 'date'],
            'contractEndDate' => ['nullable', 'date', 'after_or_equal:contractStartDate'],
            'contractTerms' => ['required', 'string', 'min:30', 'max:12000'],
        ]);
        $contract = $create->execute($proposal, $actor, [
            'start_date' => $data['contractStartDate'],
            'end_date' => $data['contractEndDate'] ?: null,
            'terms' => $data['contractTerms'],
        ]);

        return redirect()->route('contracts.show', $contract);
    }

    public function changeStatus(string $status, ChangeEngagementProjectStatus $change): void
    {
        abort_if($this->project->contract()->exists(), 409, 'Manage project lifecycle from its contract.');
        $this->project = $change->execute($this->project, Auth::user(), EngagementProjectStatus::from($status));
        session()->flash('status', 'Project status updated.');
    }

    public function selectProposal(string $proposalId): void
    {
        $proposal = $this->proposal($proposalId);
        Gate::authorize('view', $proposal);
        $this->activeProposalId = $proposal->public_id;
    }

    public function sendMessage(SendProjectMessage $send): void
    {
        $this->validate(['messageText' => ['required', 'string', 'min:1', 'max:4000']]);
        $proposal = $this->activeProposal();
        abort_if($proposal === null, 404);
        $send->execute($proposal, Auth::user(), $this->messageText);
        $this->messageText = '';
    }

    public function render(): View
    {
        $canManage = Auth::check() && Gate::allows('manage', $this->project);
        $ownProposal = Auth::check() ? $this->ownProposal() : null;
        $activeProposal = $this->activeProposal();
        $proposals = $canManage ? $this->project->proposals()->with(['freelancer.profile.skills', 'freelancer.profile.primaryLocation', 'milestones', 'portfolioItems'])->latest('submitted_at')->get() : collect();
        $contract = $this->project->contract()->first();
        $portfolioItems = Auth::check() ? FreelancerProject::query()->whereHas('freelancerProfile', fn ($query) => $query->whereHas('talentProfile', fn ($profile) => $profile->where('user_id', Auth::id())))->where('is_visible', true)->orderByDesc('completed_at')->get() : collect();

        $indexable = $this->project->status === EngagementProjectStatus::Published
            && $this->project->company->isPublished()
            && Str::length(trim($this->project->description)) >= (int) config('seo.minimum_project_description_characters', 180);
        $canonical = route('projects.show', $this->project);
        $description = Str::limit(strip_tags($this->project->description), 155);

        return view('livewire.projects.project-workspace', [
            'canManage' => $canManage,
            'ownProposal' => $ownProposal,
            'activeProposal' => $activeProposal?->load(['messages.author', 'milestones', 'portfolioItems']),
            'proposals' => $proposals,
            'contract' => $contract,
            'portfolioItems' => $portfolioItems,
            'saved' => Auth::check() && Auth::user()->savedEngagementProjects()->whereKey($this->project->id)->exists(),
            'proposalStatuses' => ProjectProposalStatus::cases(),
        ])->extends('layouts.livewire', [
            'title' => $this->project->title.' · '.$this->project->company->name.' · M Talent',
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $indexable ? 'index,follow' : 'noindex,follow',
            'structuredData' => $indexable ? [
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => $this->project->title,
                'description' => strip_tags($this->project->description),
                'url' => $canonical,
                'provider' => ['@type' => 'Organization', 'name' => $this->project->company->name, 'url' => route('companies.show', $this->project->company->slug)],
                'serviceType' => 'Freelance technology project',
                'areaServed' => $this->project->is_remote ? null : $this->project->location?->name,
            ] : null,
        ]);
    }

    private function ownProposal(): ?ProjectProposal
    {
        return Auth::check() ? $this->project->proposals()->where('freelancer_id', Auth::id())->first() : null;
    }

    private function proposal(string $publicId): ProjectProposal
    {
        return $this->project->proposals()->where('public_id', $publicId)->firstOrFail();
    }

    private function activeProposal(): ?ProjectProposal
    {
        if ($this->activeProposalId === null) {
            return $this->ownProposal();
        }

        $proposal = $this->project->proposals()->where('public_id', $this->activeProposalId)->first();

        return $proposal !== null && Gate::allows('view', $proposal) ? $proposal : null;
    }
}
