<?php

declare(strict_types=1);

use App\Domain\Verifications\Actions\ReviewVerification;
use App\Domain\Verifications\Actions\SubmitVerification;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Livewire\Admin\ReviewVerifications;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Verification;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

function grantVerificationReview(User $user): void
{
    $role = Role::query()->where('name', 'admin')->firstOrFail();
    $role->permissions()->syncWithoutDetaching(Permission::query()->where('name', 'verification.review')->pluck('id'));
    $user->roles()->attach($role->id);
}

test('verification requests start pending and an administrator decision is audited', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    grantVerificationReview($admin);

    $request = app(SubmitVerification::class)->execute($owner, VerificationType::Skill, 'Laravel', 'Public assessment result');
    expect($request->status)->toBe(VerificationStatus::Pending);

    Livewire::actingAs($admin)->test(ReviewVerifications::class)
        ->call('decide', $request->public_id, 'approved')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe(VerificationStatus::Approved)
        ->and(AuditLog::query()->where('event', 'verification.decided')->where('actor_id', $admin->id)->exists())->toBeTrue()
        ->and($owner->fresh()->verifications()->first()->isCurrentlyApproved())->toBeTrue();
});

test('only authorized administrators can review and pending requests cannot be decided twice', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $request = Verification::query()->create(['user_id' => $owner->id, 'type' => VerificationType::Education, 'label' => 'Degree']);

    expect(fn () => app(ReviewVerification::class)->execute($request, $admin, VerificationStatus::Approved, null))->toThrow(AuthorizationException::class);

    grantVerificationReview($admin);
    app(ReviewVerification::class)->execute($request, $admin, VerificationStatus::Rejected, 'Could not confirm');
    expect(fn () => app(ReviewVerification::class)->execute($request, $admin, VerificationStatus::Approved, null))->toThrow(ValidationException::class);
});

test('expired approvals do not count as verified badges', function (): void {
    $owner = User::factory()->create();
    $request = Verification::query()->create(['user_id' => $owner->id, 'type' => VerificationType::Certification, 'label' => 'Expired credential', 'status' => VerificationStatus::Approved, 'expires_at' => now()->subDay()]);
    expect($request->isCurrentlyApproved())->toBeFalse()
        ->and($request->displayStatus())->toBe(VerificationStatus::Expired);
});
