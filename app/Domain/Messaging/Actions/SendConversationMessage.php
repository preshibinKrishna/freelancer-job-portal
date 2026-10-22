<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Services\ContactInformationGuard;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Notifications\ConversationMessageReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendConversationMessage
{
    public function __construct(private readonly ContactInformationGuard $contactGuard) {}

    /** @param list<UploadedFile> $files */
    public function execute(User $sender, Conversation $conversation, string $body, array $files = []): ConversationMessage
    {
        Gate::forUser($sender)->authorize('send', $conversation);
        $body = trim($body);
        if ($body === '' && $files === []) {
            throw ValidationException::withMessages(['body' => 'Write a message before sending.']);
        }
        if ($this->contactGuard->containsContactDetails($body)) {
            throw ValidationException::withMessages(['body' => 'For privacy, email addresses and phone numbers cannot be shared in messages.']);
        }

        $stored = [];
        try {
            foreach ($files as $file) {
                $path = $file->storeAs('conversation-attachments/'.$conversation->public_id, Str::ulid().'.'.$file->guessExtension(), 'local');
                $stored[] = ['file' => $file, 'path' => $path];
            }
            [$message, $recipients] = DB::transaction(function () use ($sender, $conversation, $body, $stored): array {
                $message = $conversation->messages()->create(['sender_id' => $sender->id, 'body' => $body]);
                foreach ($stored as $item) {
                    /** @var UploadedFile $file */
                    $file = $item['file'];
                    $message->attachments()->create([
                        'disk' => 'local', 'path' => $item['path'], 'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream', 'size' => $file->getSize(),
                    ]);
                }
                $conversation->forceFill(['last_message_at' => $message->created_at])->save();
                $conversation->participants()->updateExistingPivot($sender->id, ['last_read_at' => now(), 'archived_at' => null]);
                $recipients = $conversation->participants()->whereKeyNot($sender->id)->get();
                foreach ($recipients as $recipient) {
                    $conversation->participants()->updateExistingPivot($recipient->id, ['archived_at' => null]);
                }

                return [$message, $recipients];
            });
        } catch (\Throwable $exception) {
            foreach ($stored as $item) {
                Storage::disk('local')->delete($item['path']);
            }
            throw $exception;
        }

        DB::afterCommit(function () use ($recipients, $conversation, $message): void {
            foreach ($recipients as $recipient) {
                $recipient->notify(new ConversationMessageReceived($conversation, $message));
            }
        });

        return $message;
    }
}
