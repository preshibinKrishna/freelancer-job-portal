<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Messaging\Services\ConversationAccess;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return app(ConversationAccess::class)->canView($user, $conversation);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        if (! $this->view($user, $conversation)) {
            return false;
        }

        $other = $conversation->participants()->whereKeyNot($user->id)->first();

        return $other instanceof User && ! app(ConversationAccess::class)->isBlocked($user, $other);
    }
}
