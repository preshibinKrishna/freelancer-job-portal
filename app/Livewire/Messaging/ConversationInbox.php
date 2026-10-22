<?php

declare(strict_types=1);

namespace App\Livewire\Messaging;

use App\Domain\Messaging\Actions\SendConversationMessage;
use App\Models\Conversation;
use App\Models\ConversationReport;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ConversationInbox extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?string $conversationId = null;

    public string $body = '';

    /** @var list<TemporaryUploadedFile> */
    public array $attachments = [];

    public bool $archived = false;

    public function mount(?Conversation $conversation = null): void
    {
        if ($conversation) {
            Gate::authorize('view', $conversation);
            $this->conversationId = $conversation->public_id;
            $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);
        }
    }

    public function send(SendConversationMessage $send): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg'],
        ]);
        $conversation = $this->selectedConversation();
        $send->execute(Auth::user(), $conversation, $this->body, $this->attachments);
        $this->body = '';
        $this->attachments = [];
    }

    public function archive(): void
    {
        $conversation = $this->selectedConversation();
        Gate::authorize('view', $conversation);
        $conversation->participants()->updateExistingPivot(Auth::id(), ['archived_at' => now()]);
        $this->conversationId = null;
        $this->archived = false;
    }

    public function toggleArchived(): void
    {
        $this->archived = ! $this->archived;
        $this->conversationId = null;
        $this->resetPage(pageName: 'conversationsPage');
        $this->resetPage(pageName: 'messagesPage');
    }

    public function refreshRead(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = Conversation::query()->where('public_id', $this->conversationId)->firstOrFail();
        Gate::authorize('view', $conversation);
        $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);
    }

    public function block(): void
    {
        $conversation = $this->selectedConversation();
        $other = $conversation->participants()->whereKeyNot(Auth::id())->firstOrFail();
        Auth::user()->blocks()->syncWithoutDetaching([$other->id]);
        $this->conversationId = null;
    }

    public function report(): void
    {
        $conversation = $this->selectedConversation();
        $other = $conversation->participants()->whereKeyNot(Auth::id())->firstOrFail();
        ConversationReport::query()->firstOrCreate([
            'conversation_id' => $conversation->id, 'reporter_id' => Auth::id(), 'reported_user_id' => $other->id,
        ], ['reason' => 'conduct']);
        session()->flash('status', 'Your report was recorded for review.');
    }

    public function render(): View
    {
        $user = Auth::user();
        $conversationQuery = $user->conversations();
        if ($this->archived) {
            $conversationQuery->wherePivotNotNull('archived_at');
        } else {
            $conversationQuery->wherePivotNull('archived_at');
        }
        $conversations = $conversationQuery
            ->with(['participants', 'messages' => fn ($query) => $query->reorder()->latest()->limit(1), 'context'])
            ->orderByDesc('last_message_at')
            ->paginate(20, ['conversations.*'], 'conversationsPage');
        $selected = $this->conversationId ? Conversation::query()->with('participants')
            ->where('public_id', $this->conversationId)->firstOrFail() : null;
        if ($selected) {
            Gate::authorize('view', $selected);
        }
        $other = $selected?->participants->first(fn (User $participant): bool => $participant->id !== $user->id);
        $messages = $selected?->messages()
            ->with(['sender', 'attachments'])
            ->reorder()
            ->latest()
            ->paginate(50, ['*'], 'messagesPage');
        $messages?->setCollection($messages->getCollection()->reverse()->values());
        $unread = DB::table('conversation_participants as cp')
            ->leftJoin('conversation_messages as cm', function (JoinClause $join) use ($user): void {
                $join->on('cm.conversation_id', '=', 'cp.conversation_id')
                    ->where('cm.sender_id', '!=', $user->id)
                    ->where(function (JoinClause $readAt): void {
                        $readAt->whereNull('cp.last_read_at')->orWhereColumn('cm.created_at', '>', 'cp.last_read_at');
                    });
            })
            ->where('cp.user_id', $user->id)
            ->whereNull('cp.archived_at')
            ->count('cm.id');

        return view('livewire.messaging.conversation-inbox', compact('conversations', 'selected', 'messages', 'other', 'unread'))
            ->layout('layouts.livewire', ['title' => 'Messages']);
    }

    private function selectedConversation(): Conversation
    {
        $conversation = Conversation::query()->where('public_id', $this->conversationId)->firstOrFail();
        Gate::authorize('send', $conversation);

        return $conversation;
    }
}
