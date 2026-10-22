<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Services\ConversationAccess;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartConversation
{
    public function __construct(private readonly ConversationAccess $access) {}

    public function execute(User $actor, Model $context): Conversation
    {
        abort_unless($this->access->canStart($actor, $context), 403);
        $participants = $this->access->participants($actor, $context);
        if (count($participants) !== 2) {
            throw ValidationException::withMessages(['conversation' => 'This record does not have an available conversation partner.']);
        }
        if ($this->access->isBlocked($actor, $participants[1])) {
            throw ValidationException::withMessages(['conversation' => 'This conversation is unavailable.']);
        }

        return DB::transaction(function () use ($actor, $context, $participants): Conversation {
            $conversation = Conversation::query()->firstOrCreate([
                'context_type' => $context->getMorphClass(), 'context_id' => $context->getKey(),
            ], ['created_by' => $actor->id]);
            if (! $conversation->wasRecentlyCreated) {
                abort_unless($conversation->participants()->whereKey($actor->id)->exists(), 403);
            } else {
                foreach ($participants as $participant) {
                    $conversation->participants()->syncWithoutDetaching([$participant->id]);
                }
            }

            return $conversation;
        });
    }
}
