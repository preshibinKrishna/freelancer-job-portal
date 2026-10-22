<?php

declare(strict_types=1);

use App\Domain\Contracts\Actions\ActivateContract;
use App\Domain\Contracts\Actions\ChangeContractStatus;
use App\Domain\Contracts\Actions\CreateContractFromAward;
use App\Domain\Contracts\Actions\TransitionContractMilestone;
use App\Domain\Projects\Actions\AwardProjectProposal;
use App\Enums\ContractMilestoneStatus;
use App\Enums\EngagementProjectStatus;
use App\Enums\FreelanceContractStatus;
use App\Livewire\Contracts\ContractWorkspace;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\FreelanceContract;
use App\Models\FreelancerProfile;
use App\Models\ProjectProposal;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

function contractFreelancer(): User
{
    $user = User::factory()->create();
    $user->roles()->syncWithoutDetaching([Role::query()->where('name', 'candidate')->value('id'), Role::query()->where('name', 'freelancer')->value('id')]);
    $profile = UserProfile::factory()->for($user)->create(['visibility' => 'public']);
    FreelancerProfile::query()->create(['user_profile_id' => $profile->id]);

    return $user;
}

/** @return array{User, User, EngagementProject, ProjectProposal, FreelanceContract} */
function awardedContractFixture(int $milestoneCount = 2): array
{
    $client = User::factory()->create();
    $freelancer = contractFreelancer();
    $company = Company::factory()->published()->create(['owner_id' => $client->id]);
    $project = EngagementProject::factory()->for($company)->for($client, 'creator')->published()->create(['currency' => 'USD']);
    $proposal = ProjectProposal::factory()->for($project)->for($freelancer, 'freelancer')->create(['proposed_price' => '12000.00', 'currency' => 'USD']);
    for ($position = 1; $position <= $milestoneCount; $position++) {
        $proposal->milestones()->create(['title' => 'Delivery phase '.$position, 'description' => 'A defined and reviewable project outcome.', 'amount' => '6000.00', 'duration_days' => 14, 'position' => $position]);
    }
    app(AwardProjectProposal::class)->execute($proposal, $client);
    $contract = app(CreateContractFromAward::class)->execute($proposal->fresh(), $client, [
        'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addMonth()->toDateString(),
        'terms' => 'Deliver the agreed scope in the listed phases with review at each handoff.',
    ]);

    return [$client, $freelancer, $project, $proposal->fresh(), $contract];
}

test('an awarded project becomes one draft contract with a snapshot of parties price terms dates and milestones', function (): void {
    [$client, $freelancer, $project, $proposal, $contract] = awardedContractFixture();

    expect($contract->status)->toBe(FreelanceContractStatus::Draft)
        ->and($contract->project->is($project))->toBeTrue()
        ->and($contract->client->is($client))->toBeTrue()
        ->and($contract->freelancer->is($freelancer))->toBeTrue()
        ->and($contract->proposal->is($proposal))->toBeTrue()
        ->and($contract->agreed_amount)->toBe('12000.00')
        ->and($contract->currency)->toBe('USD')
        ->and($contract->terms)->toContain('review at each handoff')
        ->and($contract->milestones)->toHaveCount(2)
        ->and($contract->milestones->first()->status)->toBe(ContractMilestoneStatus::Pending)
        ->and(DB::table('contract_status_changes')->where('freelance_contract_id', $contract->id)->count())->toBe(1);

    expect(fn () => app(CreateContractFromAward::class)->execute($proposal, $client, ['start_date' => now()->toDateString(), 'end_date' => null, 'terms' => 'Duplicate contract terms.']))
        ->toThrow(ValidationException::class);
});

test('only the named freelancer can accept a draft contract and activation starts the engagement', function (): void {
    [$client, $freelancer, $project, , $contract] = awardedContractFixture();
    $outsider = User::factory()->create();

    expect(Gate::forUser($client)->allows('view', $contract))->toBeTrue()
        ->and(Gate::forUser($freelancer)->allows('accept', $contract))->toBeTrue()
        ->and(Gate::forUser($outsider)->allows('view', $contract))->toBeFalse();
    expect(fn () => app(ActivateContract::class)->execute($contract, $outsider))->toThrow(AuthorizationException::class);

    $active = app(ActivateContract::class)->execute($contract, $freelancer);
    expect($active->status)->toBe(FreelanceContractStatus::Active)
        ->and($active->activated_at)->not->toBeNull()
        ->and($project->fresh()->status)->toBe(EngagementProjectStatus::InProgress);
    expect(fn () => app(ActivateContract::class)->execute($active, $freelancer))->toThrow(ValidationException::class);
});

test('contract timeline is visible only to its parties and the freelancer can accept from the workspace', function (): void {
    [, $freelancer, , , $contract] = awardedContractFixture(1);
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('contracts.show', $contract))->assertForbidden();
    $this->actingAs($freelancer)->get(route('contracts.show', $contract))->assertOk()
        ->assertSee('Milestone timeline')->assertSee('Delivery phase 1')->assertSee('Accept contract');
    Livewire::actingAs($freelancer)->test(ContractWorkspace::class, ['contract' => $contract])
        ->call('accept')->assertHasNoErrors()->assertSee('Active')->assertDontSee('Accept contract');
});

test('milestone state machine enforces party roles through dispute resolution and release', function (): void {
    [$client, $freelancer, , , $contract] = awardedContractFixture(1);
    $outsider = User::factory()->create();
    $contract = app(ActivateContract::class)->execute($contract, $freelancer);
    $milestone = $contract->milestones->first();
    $transition = app(TransitionContractMilestone::class);

    expect(fn () => $transition->execute($milestone, $freelancer, ContractMilestoneStatus::Funded))->toThrow(ValidationException::class);
    $milestone = $transition->execute($milestone, $client, ContractMilestoneStatus::Funded);
    expect($milestone->status)->toBe(ContractMilestoneStatus::Funded);
    expect(fn () => $transition->execute($milestone, $client, ContractMilestoneStatus::InProgress))->toThrow(ValidationException::class);

    $milestone = $transition->execute($milestone, $freelancer, ContractMilestoneStatus::InProgress);
    $milestone = $transition->execute($milestone, $freelancer, ContractMilestoneStatus::Submitted);
    expect($milestone->submitted_at)->not->toBeNull();
    expect(fn () => $transition->execute($milestone, $outsider, ContractMilestoneStatus::Disputed))->toThrow(AuthorizationException::class);

    $milestone = $transition->execute($milestone, $freelancer, ContractMilestoneStatus::Disputed);
    $milestone = $transition->execute($milestone, $client, ContractMilestoneStatus::InProgress);
    $milestone = $transition->execute($milestone, $freelancer, ContractMilestoneStatus::Submitted);
    $milestone = $transition->execute($milestone, $client, ContractMilestoneStatus::Approved);
    expect($milestone->approved_at)->not->toBeNull();
    expect(fn () => $transition->execute($milestone, $freelancer, ContractMilestoneStatus::Released))->toThrow(ValidationException::class);

    $milestone = $transition->execute($milestone, $client, ContractMilestoneStatus::Released);
    expect($milestone->status)->toBe(ContractMilestoneStatus::Released)
        ->and($milestone->released_at)->not->toBeNull()
        ->and(DB::table('contract_milestone_status_changes')->where('contract_milestone_id', $milestone->id)->count())->toBe(8);
    expect(fn () => $transition->execute($milestone, $client, ContractMilestoneStatus::Pending))->toThrow(ValidationException::class);
});

test('contracts complete only after every milestone is released and cannot transition after completion', function (): void {
    [$client, $freelancer, , , $contract] = awardedContractFixture(2);
    $contract = app(ActivateContract::class)->execute($contract, $freelancer);
    $change = app(ChangeContractStatus::class);

    expect(fn () => $change->execute($contract, $client, FreelanceContractStatus::Completed))->toThrow(ValidationException::class);
    foreach ($contract->milestones as $milestone) {
        $milestone = app(TransitionContractMilestone::class)->execute($milestone, $client, ContractMilestoneStatus::Funded);
        $milestone = app(TransitionContractMilestone::class)->execute($milestone, $freelancer, ContractMilestoneStatus::InProgress);
        $milestone = app(TransitionContractMilestone::class)->execute($milestone, $freelancer, ContractMilestoneStatus::Submitted);
        $milestone = app(TransitionContractMilestone::class)->execute($milestone, $client, ContractMilestoneStatus::Approved);
        app(TransitionContractMilestone::class)->execute($milestone, $client, ContractMilestoneStatus::Released);
    }
    $completed = $change->execute($contract, $client, FreelanceContractStatus::Completed);
    expect($completed->status)->toBe(FreelanceContractStatus::Completed)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($completed->project->status)->toBe(EngagementProjectStatus::Completed);
    expect(fn () => app(TransitionContractMilestone::class)->execute($completed->milestones->first(), $client, ContractMilestoneStatus::Funded))
        ->toThrow(ValidationException::class);
});

test('either contract party can cancel an open agreement but unrelated users cannot', function (): void {
    [$client, $freelancer, , , $contract] = awardedContractFixture(0);
    $outsider = User::factory()->create();

    expect(fn () => app(ChangeContractStatus::class)->execute($contract, $outsider, FreelanceContractStatus::Cancelled))
        ->toThrow(AuthorizationException::class);
    $cancelled = app(ChangeContractStatus::class)->execute($contract, $freelancer, FreelanceContractStatus::Cancelled);
    expect($cancelled->status)->toBe(FreelanceContractStatus::Cancelled)
        ->and($cancelled->project->status)->toBe(EngagementProjectStatus::Cancelled);
    expect(fn () => app(ActivateContract::class)->execute($cancelled, $freelancer))->toThrow(ValidationException::class);
});
