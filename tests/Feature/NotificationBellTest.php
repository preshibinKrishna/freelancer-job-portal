<?php

declare(strict_types=1);

use App\Livewire\Notifications\NotificationBell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createNotification(User $user, string $message, string $url): string
{
    $id = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $id,
        'type' => 'demo.notification',
        'data' => ['message' => $message, 'url' => $url],
    ]);

    return $id;
}

test('notification bell displays only the signed-in user notifications', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    createNotification($user, 'Your application was received.', '/candidate/applications');
    createNotification($other, 'Private update for another account.', '/messages');

    Livewire::actingAs($user)->test(NotificationBell::class)
        ->call('toggle')
        ->assertSee('Your application was received.')
        ->assertDontSee('Private update for another account.');
});

test('authenticated headers render the notification component instead of exposing its Blade directive', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('jobs.index'))
        ->assertOk()
        ->assertSee('aria-label="Notifications', false)
        ->assertDontSee("@livewire('notifications.notification-bell', [])", false);
});

test('notification bell only visits notifications belonging to the signed-in user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notificationId = createNotification($other, 'Private update.', '/messages');

    Livewire::actingAs($user)->test(NotificationBell::class)
        ->call('visit', $notificationId)
        ->assertStatus(404);

    expect($other->notifications()->whereKey($notificationId)->firstOrFail()->read_at)->toBeNull();
});

test('notification bell rejects external and browser-normalized redirect targets before marking read', function (string $url): void {
    $user = User::factory()->create();
    $notificationId = createNotification($user, 'Unsafe target.', $url);

    Livewire::actingAs($user)->test(NotificationBell::class)
        ->call('toggle')
        ->assertSee('href="#"', false)
        ->call('visit', $notificationId)
        ->assertStatus(404);

    expect($user->notifications()->whereKey($notificationId)->firstOrFail()->read_at)->toBeNull();
})->with([
    'absolute external URL' => ['https://attacker.example.test/path'],
    'protocol relative URL' => ['//attacker.example.test/path'],
    'backslash-normalized URL' => ['/\\attacker.example.test/path'],
]);

test('notification bell marks a valid internal notification read and redirects', function (): void {
    $user = User::factory()->create();
    $notificationId = createNotification($user, 'Your application was received.', '/candidate/applications');

    Livewire::actingAs($user)->test(NotificationBell::class)
        ->call('visit', $notificationId)
        ->assertRedirect('/candidate/applications');

    expect($user->notifications()->whereKey($notificationId)->firstOrFail()->read_at)->not->toBeNull();
});
