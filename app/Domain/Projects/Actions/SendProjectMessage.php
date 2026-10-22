<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Models\ProjectMessage;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SendProjectMessage
{
    public function execute(ProjectProposal $proposal, User $actor, string $body): ProjectMessage
    {
        Gate::forUser($actor)->authorize('view', $proposal);
        if (blank(trim($body)) || mb_strlen($body) > 4000) {
            throw ValidationException::withMessages(['message' => 'Write a message of up to 4,000 characters.']);
        }

        return $proposal->messages()->create(['author_id' => $actor->id, 'body' => trim($body)]);
    }
}
