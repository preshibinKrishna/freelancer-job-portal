<?php

declare(strict_types=1);

use App\Domain\Projects\Actions\AwardProjectProposal;
use App\Domain\Projects\Actions\ChangeEngagementProjectStatus;
use App\Domain\Projects\Actions\ReviewProjectProposal;
use App\Domain\Projects\Actions\SendProjectMessage;
use App\Domain\Projects\Actions\SubmitProjectProposal;
use App\Domain\Projects\Actions\WithdrawProjectProposal;
use App\Enums\EngagementProjectStatus;
use App\Enums\ProjectProposalStatus;
use App\Livewire\Projects\BrowseProjects;
use App\Livewire\Projects\ManageEngagementProject;
use App\Livewire\Projects\ProjectWorkspace;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\ProjectCategory;
use App\Models\ProjectProposal;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

function giveMarketplaceRole(User $user, string $role): void
{
    $user->roles()->syncWithoutDetaching([Role::query()->where('name', $role)->value('id')]);
}

function makePublishedEngagementProject(?User $employer = null, array $attributes = []): array
{
    $employer ??= User::factory()->create();
    $company = Company::factory()->published()->create(['owner_id' => $employer->id]);
    $project = EngagementProject::factory()->for($company)->for($employer, 'creator')->published()->create([
        'title' => 'Build an event processing service',
        'description' => 'Build a reliable event processing service for a growing product team, with clear operational documentation.',
        'currency' => 'USD',
        ...$attributes,
    ]);

    return [$employer, $company, $project];
}

function makeFreelancer(): User
{
    $freelancer = User::factory()->create();
    giveMarketplaceRole($freelancer, 'candidate');
    giveMarketplaceRole($freelancer, 'freelancer');
    $talent = UserProfile::factory()->for($freelancer)->create(['visibility' => 'public']);
    FreelancerProfile::query()->create(['user_profile_id' => $talent->id]);

    return $freelancer;
}

test('employers can create and publish complete project briefs through the managed workspace', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->published()->create(['owner_id' => $employer->id]);
    $description = 'Create a resilient workflow service, including implementation, deployment documentation, and handover to our team.';

    Livewire::actingAs($employer)->test(ManageEngagementProject::class)
        ->set('companyId', (string) $company->id)
        ->set('title', 'Create a workflow service')
        ->set('description', $description)
        ->set('currency', 'USD')
        ->set('pricingType', 'fixed')
        ->set('budgetMin', '5000')
        ->set('budgetMax', '12000')
        ->set('estimatedDurationDays', '60')
        ->set('experienceMin', '4')
        ->set('skillsInput', 'PHP, Laravel')
        ->set('isRemote', true)
        ->set('applicationDeadline', now()->addWeek()->toDateString())
        ->call('publish')
        ->assertHasNoErrors()
        ->assertRedirect();

    $project = EngagementProject::query()->firstOrFail();
    expect($project->status)->toBe(EngagementProjectStatus::Published)
        ->and($project->budget_min)->toBe('5000.00')
        ->and($project->budget_max)->toBe('12000.00')
        ->and($project->pricing_type->value)->toBe('fixed')
        ->and($project->is_remote)->toBeTrue()
        ->and($project->skills()->count())->toBe(2)
        ->and(DB::table('engagement_project_status_changes')->where('engagement_project_id', $project->id)->count())->toBe(1);
});

test('project discovery only includes published briefs and filters by skills and project terms', function (): void {
    [, , $open] = makePublishedEngagementProject();
    $open->skills()->attach(Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']));
    $draftCompany = Company::factory()->published()->create();
    EngagementProject::factory()->for($draftCompany)->create(['title' => 'Private draft project']);

    $component = Livewire::test(BrowseProjects::class)
        ->set('skill', 'laravel')
        ->set('pricing', 'fixed');

    expect($component->viewData('projects')->total())->toBe(1);
    $this->get(route('projects.show', $open))->assertOk()->assertSee($open->title);
    $this->get(route('projects.index'))->assertOk()->assertSee($open->title)->assertDontSee('Private draft project');
});

test('freelancers submit scoped proposals with price timeline milestones and owned portfolio work', function (): void {
    [, , $project] = makePublishedEngagementProject();
    $freelancer = makeFreelancer();
    $portfolio = $freelancer->profile->freelancerProfile->projects()->create([
        'project_category_id' => ProjectCategory::factory()->create()->id,
        'title' => 'A public portfolio project',
        'summary' => 'A real project from the freelancer portfolio.',
    ]);

    $proposal = app(SubmitProjectProposal::class)->execute($project, $freelancer, [
        'cover_letter' => 'I would start by mapping the event boundaries, then build a testable service and document the operational handoff.',
        'proposed_price' => '8500', 'currency' => 'USD', 'proposed_duration_days' => 45,
        'portfolio_ids' => [$portfolio->public_id],
        'milestones' => [['title' => 'Architecture and event contracts', 'description' => 'Agree on interfaces and delivery checkpoints.', 'amount' => '2000', 'duration_days' => 10]],
    ]);

    expect($proposal->status)->toBe(ProjectProposalStatus::Submitted)
        ->and($proposal->freelancer_id)->toBe($freelancer->id)
        ->and($proposal->milestones)->toHaveCount(1)
        ->and($proposal->portfolioItems->first()->is($portfolio))->toBeTrue();
    expect(fn () => app(SubmitProjectProposal::class)->execute($project, $freelancer, [
        'cover_letter' => 'A duplicate proposal with enough detail to pass validation.', 'proposed_price' => '8500', 'currency' => 'USD',
        'proposed_duration_days' => 45, 'portfolio_ids' => [], 'milestones' => [],
    ]))->toThrow(AuthorizationException::class);
});

test('employers can shortlist award and progress an engagement while milestones are carried over', function (): void {
    [$employer, , $project] = makePublishedEngagementProject();
    $freelancer = makeFreelancer();
    $proposal = ProjectProposal::factory()->for($project)->for($freelancer, 'freelancer')->create(['currency' => 'USD']);
    $proposal->milestones()->create(['title' => 'Implementation', 'description' => 'Deliver the tested service.', 'amount' => '4000', 'duration_days' => 30, 'position' => 1]);

    app(ReviewProjectProposal::class)->execute($proposal, $employer, ProjectProposalStatus::Shortlisted);
    $awarded = app(AwardProjectProposal::class)->execute($proposal, $employer);
    expect($awarded->status)->toBe(ProjectProposalStatus::Awarded)
        ->and($project->fresh()->status)->toBe(EngagementProjectStatus::Awarded)
        ->and($project->fresh()->milestones)->toHaveCount(1);

    app(ChangeEngagementProjectStatus::class)->execute($project->fresh(), $employer, EngagementProjectStatus::InProgress);
    app(ChangeEngagementProjectStatus::class)->execute($project->fresh(), $employer, EngagementProjectStatus::Completed);
    expect($project->fresh()->status)->toBe(EngagementProjectStatus::Completed)
        ->and(DB::table('engagement_project_status_changes')->where('engagement_project_id', $project->id)->count())->toBe(3);
});

test('proposal conversations are private to its freelancer and the company hiring team', function (): void {
    [$employer, , $project] = makePublishedEngagementProject();
    $freelancer = makeFreelancer();
    $outsider = User::factory()->create();
    $proposal = ProjectProposal::factory()->for($project)->for($freelancer, 'freelancer')->create();

    $message = app(SendProjectMessage::class)->execute($proposal, $freelancer, 'I can start with a short technical discovery session.');
    expect($message->author_id)->toBe($freelancer->id)
        ->and(Gate::forUser($employer)->allows('view', $proposal))->toBeTrue()
        ->and(Gate::forUser($outsider)->allows('view', $proposal))->toBeFalse();
    expect(fn () => app(SendProjectMessage::class)->execute($proposal, $outsider, 'Can you send me the client contact?'))
        ->toThrow(AuthorizationException::class);

    $this->actingAs($outsider);
    Livewire::test(ProjectWorkspace::class, ['project' => $project])
        ->set('activeProposalId', $proposal->public_id)
        ->assertDontSee('I can start with a short technical discovery session.');
});

test('freelancers can withdraw open proposals but rejected proposals cannot be withdrawn', function (): void {
    [, , $project] = makePublishedEngagementProject();
    $freelancer = makeFreelancer();
    $proposal = ProjectProposal::factory()->for($project)->for($freelancer, 'freelancer')->create();

    $withdrawn = app(WithdrawProjectProposal::class)->execute($proposal, $freelancer);
    expect($withdrawn->status)->toBe(ProjectProposalStatus::Withdrawn)
        ->and($withdrawn->withdrawn_at)->not->toBeNull();
});

test('only eligible company team members can edit projects or review proposals', function (): void {
    [$employer, , $project] = makePublishedEngagementProject();
    $outsider = User::factory()->create();
    $proposal = ProjectProposal::factory()->for($project)->for(makeFreelancer(), 'freelancer')->create();

    expect(Gate::forUser($employer)->allows('manage', $project))->toBeTrue()
        ->and(Gate::forUser($outsider)->allows('manage', $project))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('review', $proposal))->toBeFalse();

    Livewire::actingAs($outsider)->test(ManageEngagementProject::class, ['project' => $project])->assertForbidden();
});

test('private project attachments are policy streamed and never exposed as public storage urls', function (): void {
    Storage::fake('local');
    [$employer, , $project] = makePublishedEngagementProject();
    $path = 'projects/'.strtolower($project->public_id).'/attachments/brief.pdf';
    Storage::disk('local')->put($path, "%PDF-1.4\nProject brief");
    $attachment = $project->attachments()->create(['disk' => 'local', 'path' => $path, 'original_filename' => 'brief.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 23]);

    $this->actingAs($employer)->get(route('projects.attachments.download', $attachment))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    auth()->logout();
    $this->get(route('projects.attachments.download', $attachment))->assertRedirect(route('login'));
});
