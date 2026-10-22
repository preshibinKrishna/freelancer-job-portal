<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ProjectProposalPolicy
{
    public function view(User $actor, ProjectProposal $proposal): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail()
            && ($proposal->freelancer_id === $actor->id || Gate::forUser($actor)->allows('manage', $proposal->project));
    }

    public function withdraw(User $actor, ProjectProposal $proposal): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && $proposal->freelancer_id === $actor->id;
    }

    public function review(User $actor, ProjectProposal $proposal): bool
    {
        return $actor->isActive() && $actor->hasVerifiedEmail() && Gate::forUser($actor)->allows('manage', $proposal->project);
    }
}
