<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConversationMessageReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly Conversation $conversation, private readonly ConversationMessage $message) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'You have a new internal message.',
            'url' => route('messages.show', $this->conversation, false),
            'conversation_id' => $this->conversation->public_id,
            'message_id' => $this->message->public_id,
        ];
    }
}
