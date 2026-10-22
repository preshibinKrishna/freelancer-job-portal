<?php

declare(strict_types=1);

use App\Domain\Identity\Actions\ChangeAccountStatus;
use App\Enums\AccountStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

test('registration creates a candidate account and sends verification', function (): void {
    Notification::fake();
    $response = $this->post(route('register'), [
        'name' => 'Aisha Rahman', 'email' => 'AISHA@example.com', 'password' => 'SecurePass123!xyz',
        'password_confirmation' => 'SecurePass123!xyz', 'terms' => '1',
    ]);

    $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();
    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticatedAs($user);
    expect($user->hasRole('candidate'))->toBeTrue()
        ->and($user->hasRole('admin', 'super_admin', 'employer', 'recruiter', 'freelancer'))->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class, fn (VerifyEmail $notification): bool => str_contains(
        $notification->toMail($user)->actionUrl,
        $user->public_id,
    ));
    $this->assertDatabaseHas('audit_logs', ['subject_id' => $user->id, 'event' => 'account.registered']);
});

test('registration supports a separate employer account capability', function (): void {
    $this->post(route('register'), [
        'name' => 'Morgan Employer', 'email' => 'morgan@example.com', 'account_type' => 'employer',
        'password' => 'SecurePass123!xyz', 'password_confirmation' => 'SecurePass123!xyz', 'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    $employer = User::query()->where('email', 'morgan@example.com')->firstOrFail();
    expect($employer->hasRole('employer'))->toBeTrue()
        ->and($employer->hasRole('candidate', 'admin', 'super_admin', 'freelancer', 'recruiter'))->toBeFalse();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'user' => $employer->public_id, 'hash' => sha1($employer->getEmailForVerification()),
    ]);
    $this->get($url)->assertRedirect(route('employer.companies.create'));
});

test('registration requires password strength and terms acceptance', function (): void {
    $this->from(route('register'))->post(route('register'), [
        'name' => 'Aisha', 'email' => 'aisha@example.com', 'password' => 'short', 'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['password', 'terms']);

    $this->assertDatabaseCount('users', 0);
});

test('login regenerates the session and logout invalidates it', function (): void {
    $user = User::factory()->create(['email' => 'person@example.com', 'password' => 'SecurePass123!xyz']);
    $sessionId = $this->app['session']->getId();

    $this->post(route('login.store'), ['email' => 'PERSON@example.com', 'password' => 'SecurePass123!xyz'])
        ->assertRedirect(route('profile.edit'));

    $this->assertAuthenticatedAs($user);
    $this->assertNotSame($sessionId, $this->app['session']->getId());
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
});

test('login throttles repeated invalid attempts', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'person@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => 'person@example.com', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('suspended accounts cannot log in or continue using existing sessions', function (): void {
    $user = User::factory()->create(['password' => 'SecurePass123!xyz']);
    $user->update(['status' => AccountStatus::Suspended]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'SecurePass123!xyz'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($user)->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('signed email verification enables account profile access', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'user' => $user->public_id, 'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('profile.edit'));
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('password reset uses a token and invalidates active sessions', function (): void {
    Notification::fake();
    config(['session.driver' => 'database']);
    $user = User::factory()->create(['email' => 'person@example.com']);
    $token = null;

    $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    $oldVersion = $user->session_version;
    DB::table('sessions')->insert([
        'id' => 'pre-reset-session', 'user_id' => $user->id, 'payload' => 'payload', 'last_activity' => now()->timestamp,
    ]);
    $this->post(route('password.update'), [
        'token' => $token, 'email' => $user->email, 'password' => 'AnotherSecure456!pass',
        'password_confirmation' => 'AnotherSecure456!pass',
    ])->assertRedirect(route('login'));

    expect($user->fresh()->session_version)->toBe($oldVersion + 1);
    $this->assertTrue(password_verify('AnotherSecure456!pass', $user->fresh()->password));
    $this->assertDatabaseMissing('sessions', ['id' => 'pre-reset-session']);
});

test('suspension invalidates existing session versions', function (): void {
    $user = User::factory()->create(['password' => 'SecurePass123!xyz']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'SecurePass123!xyz']);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    app(ChangeAccountStatus::class)->suspend($user->fresh(), $admin);
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('users can update profile basics and delete their account after password confirmation', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'candidate')->firstOrFail());

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Aisha Rahman', 'headline' => 'Product designer', 'bio' => 'Designing useful tools.',
        'location' => 'Kochi', 'timezone' => 'Asia/Kolkata', 'locale' => 'en',
    ])->assertRedirect();

    $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'headline' => 'Product designer']);
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect(route('home'));
    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id, 'headline' => 'Product designer']);
    $this->assertGuest();
});

test('the last super administrator cannot delete their own account', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('account');
    expect($user->fresh()->trashed())->toBeFalse();
});

test('users can hold multiple roles and policies follow permissions', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('name', 'candidate')->firstOrFail());
    $admin = User::factory()->create();
    $superAdmin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());

    expect($user->hasRole('candidate'))->toBeTrue()
        ->and($admin->can('suspend', $user))->toBeTrue()
        ->and($admin->can('assignRoles', $user))->toBeTrue()
        ->and($superAdmin->can('assignRoles', $user))->toBeTrue();

    $user->roles()->attach(Role::query()->where('name', 'freelancer')->firstOrFail());
    expect($user->fresh()->hasRole('candidate', 'freelancer'))->toBeTrue();
});

test('privileged role assignment is policy checked and uses public user identifiers', function (): void {
    $actor = User::factory()->create();
    $actor->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());
    $target = User::factory()->create();

    $this->actingAs($actor)->put(route('admin.users.roles.assign', $target), ['role' => 'freelancer'])->assertRedirect();
    expect($target->fresh()->hasRole('freelancer'))->toBeTrue();
    $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'assigned_by' => $actor->id]);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'subject_id' => $target->id, 'event' => 'identity.role_assigned']);
});

test('non privileged users cannot assign roles and last super admin cannot be suspended', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($actor)->put(route('admin.users.roles.assign', $target), ['role' => 'employer'])->assertForbidden();

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $superAdmin = User::factory()->create();
    $superAdmin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());

    $this->actingAs($admin)->put(route('admin.users.roles.assign', $target), ['role' => 'freelancer'])->assertRedirect();
    $this->actingAs($admin)->put(route('admin.users.suspend', $superAdmin))->assertForbidden();
    expect($superAdmin->fresh()->isActive())->toBeTrue();
    $this->actingAs($admin)->put(route('admin.users.roles.assign', $target), ['role' => 'admin'])->assertForbidden();
    $this->actingAs($admin)->put(route('admin.users.roles.assign', $target), ['role' => 'super_admin'])->assertForbidden();
});

test('initial super administrator can only be provisioned from a verified active account', function (): void {
    $user = User::factory()->unverified()->create(['email' => 'admin@example.com']);

    expect(Artisan::call('identity:grant-super-admin', ['email' => $user->email, '--force' => true]))
        ->toBe(1);

    $user->markEmailAsVerified();
    expect(Artisan::call('identity:grant-super-admin', ['email' => $user->email, '--force' => true]))
        ->toBe(0)
        ->and($user->fresh()->hasRole('super_admin'))->toBeTrue();
});
