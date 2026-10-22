<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Enums\ProjectProposalStatus;
use App\Models\AuditLog;
use App\Models\EngagementProject;
use App\Models\FreelancerProject;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SubmitProjectProposal
{
    /** @param array{cover_letter: string, proposed_price: numeric-string, currency: string, proposed_duration_days: int, milestones: list<array{title: string, description: string|null, amount: numeric-string|null, duration_days: int|null}>, portfolio_ids: list<string>} $data */
    public function execute(EngagementProject $project, User $freelancer, array $data): ProjectProposal
    {
        return DB::transaction(function () use ($data, $freelancer, $project): ProjectProposal {
            $project = EngagementProject::query()->lockForUpdate()->findOrFail($project->id);
            Gate::forUser($freelancer)->authorize('propose', $project);
            if ($project->currency !== strtoupper($data['currency'])) {
                throw ValidationException::withMessages(['currency' => 'Proposals must use the project currency.']);
            }

            $profile = $freelancer->profile?->freelancerProfile;
            if ($profile === null || ! $freelancer->hasRole('freelancer')) {
                throw ValidationException::withMessages(['proposal' => 'Enable your freelancer profile before submitting a proposal.']);
            }
            $portfolioIds = collect($data['portfolio_ids'])->unique()->values();
            $portfolio = FreelancerProject::query()->whereIn('public_id', $portfolioIds)
                ->where('freelancer_profile_id', $profile->id)->where('is_visible', true)->get();
            if ($portfolio->count() !== $portfolioIds->count()) {
                throw ValidationException::withMessages(['portfolioIds' => 'Choose only visible work from your own portfolio.']);
            }

            $proposal = new ProjectProposal;
            $proposal->fill([
                'cover_letter' => trim($data['cover_letter']), 'proposed_price' => $data['proposed_price'],
                'currency' => strtoupper($data['currency']), 'proposed_duration_days' => $data['proposed_duration_days'],
                'status' => ProjectProposalStatus::Submitted, 'submitted_at' => now(),
            ]);
            $proposal->freelancer()->associate($freelancer);
            $project->proposals()->save($proposal);
            $proposal->milestones()->createMany(collect($data['milestones'])->values()->map(fn (array $milestone, int $index): array => $milestone + ['position' => $index + 1])->all());
            $proposal->portfolioItems()->sync($portfolio->modelKeys());

            AuditLog::query()->create(['actor_id' => $freelancer->id, 'subject_id' => $project->id, 'event' => 'project.proposal_submitted', 'metadata' => ['proposal_public_id' => $proposal->public_id]]);

            return $proposal->load(['project', 'milestones', 'portfolioItems']);
        });
    }
}
