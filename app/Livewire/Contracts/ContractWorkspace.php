<?php

declare(strict_types=1);

namespace App\Livewire\Contracts;

use App\Domain\Contracts\Actions\ActivateContract;
use App\Domain\Contracts\Actions\ChangeContractStatus;
use App\Domain\Contracts\Actions\TransitionContractMilestone;
use App\Enums\ContractMilestoneStatus;
use App\Enums\FreelanceContractStatus;
use App\Models\ContractMilestone;
use App\Models\FreelanceContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ContractWorkspace extends Component
{
    public FreelanceContract $contract;

    public function mount(FreelanceContract $contract): void
    {
        Gate::authorize('view', $contract);
        $this->contract = $contract->load(['project.company', 'client', 'freelancer', 'milestones']);
    }

    public function accept(ActivateContract $activate): void
    {
        $this->contract = $activate->execute($this->contract, Auth::user())->load(['project', 'milestones']);
        session()->flash('status', 'Contract accepted. The project is now in progress.');
    }

    public function transitionMilestone(string $milestoneId, string $status, TransitionContractMilestone $transition): void
    {
        $next = ContractMilestoneStatus::tryFrom($status);
        abort_if($next === null, 422);
        $milestone = $this->contract->milestones()->where('public_id', $milestoneId)->firstOrFail();
        $transition->execute($milestone, Auth::user(), $next);
        $this->contract->load('milestones');
        session()->flash('status', 'Milestone timeline updated.');
    }

    public function changeStatus(string $status, ChangeContractStatus $change): void
    {
        $next = FreelanceContractStatus::tryFrom($status);
        abort_if($next === null, 422);
        $this->contract = $change->execute($this->contract, Auth::user(), $next)->load(['project', 'milestones']);
        session()->flash('status', 'Contract status updated.');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->contract);
        $actor = Auth::user();
        $isClient = Gate::allows('manage', $this->contract);
        $isFreelancer = $actor->id === $this->contract->freelancer_id;
        $isParty = $actor->id === $this->contract->client_id || $isFreelancer;

        return view('livewire.contracts.contract-workspace', [
            'isClient' => $isClient,
            'isFreelancer' => $isFreelancer,
            'isParty' => $isParty,
            'isNamedClient' => $actor->id === $this->contract->client_id,
            'allMilestonesReleased' => $this->contract->milestones->every(fn (ContractMilestone $milestone): bool => $milestone->status === ContractMilestoneStatus::Released),
        ])->extends('layouts.livewire', ['title' => 'Contract · '.$this->contract->project->title]);
    }
}
