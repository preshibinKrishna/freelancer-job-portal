<?php

declare(strict_types=1);

use App\Domain\Messaging\Actions\SendConversationMessage;
use App\Domain\Messaging\Actions\StartConversation;
use App\Livewire\Messaging\ConversationInbox;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('internal inbox requires an authenticated verified account', function (): void {
    $this->get(route('messages.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user)->get(route('messages.index'))->assertOk()->assertSee('Messages');
});

test('conversation start accepts only supported business context types', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get(route('messages.start', ['type' => 'user', 'publicId' => '01J00000000000000000000000']))
        ->assertNotFound();
});

test('application conversations are party scoped and block contact details', function (): void {
    $employer = User::factory()->create();
    $candidate = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);
    $job = Job::factory()->create(['company_id' => $company->id, 'created_by' => $employer->id]);
    $application = JobApplication::factory()->create(['job_id' => $job->id, 'candidate_id' => $candidate->id]);

    $conversation = app(StartConversation::class)->execute($candidate, $application);
    expect($conversation->participants()->count())->toBe(2);
    expect(fn () => app(SendConversationMessage::class)->execute($candidate, $conversation, 'Contact me at candidate@example.com'))
        ->toThrow(ValidationException::class);

    app(SendConversationMessage::class)->execute($candidate, $conversation, 'Thanks for reviewing my application.');
    expect($conversation->messages()->count())->toBe(1)
        ->and($employer->notifications()->count())->toBe(1)
        ->and($employer->notifications()->first()->data['message'])->not->toContain('candidate@example.com');

    $outsider = User::factory()->create();
    expect($outsider->can('view', $conversation))->toBeFalse();
});

test('conversation inbox uses bounded pagination and avoids per-conversation unread queries', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);

    foreach (range(1, 25) as $index) {
        $conversation = Conversation::query()->create([
            'context_type' => JobApplication::class,
            'context_id' => 100_000 + $index,
            'created_by' => $user->id,
            'last_message_at' => now()->subMinutes($index),
        ]);
        $conversation->participants()->attach([$user->id, $other->id]);
        $conversation->messages()->create([
            'sender_id' => $other->id,
            'body' => 'Conversation message '.$index,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($user)->test(ConversationInbox::class)
        ->assertSee('25 unread')
        ->assertSee('Conversation message 1')
        ->assertDontSee('Conversation message 25');

    $messageQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'conversation_messages'));
    DB::disableQueryLog();

    expect($messageQueries)->toHaveCount(2);
});
