<?php

declare(strict_types=1);

use App\Domain\Companies\Actions\SaveCompanyProfile;
use App\Domain\Companies\Actions\SetCompanyMember;
use App\Domain\Jobs\Actions\ApplyToJob;
use App\Domain\Jobs\Actions\ChangeJobStatus;
use App\Domain\Jobs\Actions\DuplicateJob;
use App\Domain\Jobs\Actions\ReviewJobApplication;
use App\Domain\Jobs\Actions\SaveJob;
use App\Domain\Jobs\Actions\SaveJobForLater;
use App\Domain\Jobs\Actions\WithdrawJobApplication;
use App\Domain\Jobs\Events\JobApplicationStatusChanged;
use App\Domain\Jobs\Events\JobApplicationSubmitted;
use App\Domain\Jobs\Events\JobApplicationWithdrawn;
use App\Domain\Jobs\Listeners\NotifyCandidateOfApplicationStatus;
use App\Domain\Jobs\Listeners\NotifyCompanyOfApplicationWithdrawal;
use App\Domain\Jobs\Listeners\NotifyCompanyOfJobApplication;
use App\Enums\CompanyMemberRole;
use App\Enums\EmploymentType;
use App\Enums\JobApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Enums\WorkplaceType;
use App\Livewire\Jobs\JobActions;
use App\Livewire\Jobs\ManageJob;
use App\Livewire\Jobs\SearchJobs;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Notifications\JobApplicationReceived;
use App\Notifications\JobApplicationStatusUpdated;
use App\Notifications\JobApplicationWithdrawn as JobApplicationWithdrawnNotification;
use Database\Seeders\CompanySeeder;
use Database\Seeders\GeographicLocationSeeder;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
    $this->seed(GeographicLocationSeeder::class);
    $this->seed(CompanySeeder::class);
});

function createJobCompany(User $owner, string $name = 'Northstar Systems', bool $published = true): Company
{
    return app(SaveCompanyProfile::class)->create($owner, [
        'name' => $name,
        'industry' => 'Software Development',
        'description' => 'A product engineering company building thoughtful digital tools.',
        'website' => 'https://northstar.example',
        'company_size' => '11-50',
        'founded_year' => 2018,
        'headquarters_location_id' => Location::query()->where('name', 'Kochi')->value('id'),
        'it_park_id' => Location::query()->where('name', 'Infopark')->value('id'),
        'office_location_ids' => [],
        'social_links' => [],
        'contact_name' => null,
        'contact_email' => null,
        'contact_phone' => null,
        'benefits' => [],
        'technology_stack' => [],
        'company_culture' => null,
        'published' => $published,
    ]);
}

function createJobListing(User $owner, Company $company, array $overrides = []): Job
{
    $attributes = array_merge([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Join a collaborative team building secure, high-quality software for customers across industries.',
        'employment_type' => EmploymentType::FullTime->value,
        'workplace_type' => WorkplaceType::Hybrid->value,
        'experience_min' => 4,
        'experience_max' => 8,
        'salary_min' => '90000',
        'salary_max' => '140000',
        'currency' => 'INR',
        'location_id' => Location::query()->where('name', 'Kochi')->value('id'),
        'it_park_id' => Location::query()->where('name', 'Infopark')->value('id'),
        'responsibilities' => ['Build reliable product features'],
        'requirements' => ['Production Laravel experience'],
        'benefits' => ['Flexible work'],
        'application_deadline' => null,
        'visibility' => JobVisibility::Public->value,
        'skills' => ['Laravel', 'PHP', 'MySQL'],
    ], $overrides);

    return app(SaveJob::class)->execute($owner, $company, $attributes);
}

function assignJobRole(User $user, string $role): void
{
    $user->roles()->syncWithoutDetaching([Role::query()->where('name', $role)->firstOrFail()->id]);
}

test('company owners and recruiters can author and control job lifecycles', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company);

    $recruiter = User::factory()->create();
    app(SetCompanyMember::class)->addByEmail($company, $owner, $recruiter->email, CompanyMemberRole::Recruiter);
    $recruiterJob = createJobListing($recruiter, $company, ['title' => 'Recruiter-authored backend role']);
    expect($recruiterJob->created_by)->toBe($recruiter->id);

    expect($job->status)->toBe(JobStatus::Draft)->and($job->skills->pluck('name')->all())->toBe(['Laravel', 'PHP', 'MySQL']);
    $job = app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);
    expect($job->status)->toBe(JobStatus::Published)->and($job->published_at)->not->toBeNull();
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Paused);
    expect($job->fresh()->status)->toBe(JobStatus::Paused);
    app(ChangeJobStatus::class)->execute($job->fresh(), $owner, JobStatus::Published);
    app(ChangeJobStatus::class)->execute($job->fresh(), $owner, JobStatus::Closed);
    expect($job->fresh()->status)->toBe(JobStatus::Closed)
        ->and(AuditLog::query()->where('event', 'job.status_changed')->count())->toBe(4);

    expect(fn () => app(ChangeJobStatus::class)->execute($job->fresh(), $owner, JobStatus::Published))
        ->toThrow(ValidationException::class);
});

test('publishing requires a public company profile and valid non-expired job details', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner, 'Private Northstar', false);
    $job = createJobListing($owner, $company, ['application_deadline' => now()->subDay()->toDateString()]);

    expect(fn () => app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published))
        ->toThrow(ValidationException::class);
    expect($job->fresh()->status)->toBe(JobStatus::Draft);
});

test('employer job form creates a draft and page renders management controls', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);

    Livewire::actingAs($owner)->test(ManageJob::class)
        ->set('companyId', (string) $company->id)
        ->set('title', 'Platform Engineer')
        ->set('description', 'Work with an ambitious team to make complex systems more reliable and delightful for customers.')
        ->set('employmentType', EmploymentType::FullTime->value)
        ->set('workplaceType', WorkplaceType::Remote->value)
        ->set('skillsInput', 'PHP, Laravel, Redis')
        ->call('save')
        ->assertHasNoErrors();

    expect(Job::query()->where('title', 'Platform Engineer')->firstOrFail()->status)->toBe(JobStatus::Draft);
    $this->actingAs($owner)->get(route('employer.jobs.index'))->assertOk()->assertSee('Your job listings');
    $this->get(route('employer.jobs.create'))->assertOk()->assertSee('Role description')->assertSee('Publishing preferences');
});

test('job search filters only public discoverable listings and direct unlisted pages are noindex', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $public = createJobListing($owner, $company, ['title' => 'Senior Laravel Engineer']);
    $unlisted = createJobListing($owner, $company, ['title' => 'Private Platform Architect', 'visibility' => JobVisibility::Unlisted->value]);
    $paused = createJobListing($owner, $company, ['title' => 'Paused API Developer']);
    app(ChangeJobStatus::class)->execute($public, $owner, JobStatus::Published);
    app(ChangeJobStatus::class)->execute($unlisted, $owner, JobStatus::Published);
    app(ChangeJobStatus::class)->execute($paused, $owner, JobStatus::Published);
    app(ChangeJobStatus::class)->execute($paused->fresh(), $owner, JobStatus::Paused);

    $this->get(route('jobs.index'))->assertOk()->assertSee('Senior Laravel Engineer')->assertDontSee('Private Platform Architect')->assertDontSee('Paused API Developer');
    Livewire::test(SearchJobs::class)->set('search', 'Laravel')->assertSee('Senior Laravel Engineer')->assertDontSee('Private Platform Architect');
    $this->get(route('jobs.show', $public->slug))->assertOk()->assertSee('JobPosting')->assertSee('Senior Laravel Engineer')->assertSee('wire:snapshot', false)->assertSee('data-update-uri', false);
    $this->get(route('companies.show', $company->slug))->assertOk()->assertSee('Senior Laravel Engineer');
    $this->get(route('jobs.show', $unlisted->slug))->assertOk()->assertSee('noindex,follow');
    $this->get(route('jobs.show', $paused->slug))->assertNotFound();
});

test('authenticated job detail renders the notification bell and loads Livewire assets', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company);
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);

    $this->actingAs($owner)->get(route('jobs.show', $job->slug))
        ->assertOk()
        ->assertSee('aria-label="Notifications', false)
        ->assertSee('data-update-uri', false)
        ->assertDontSee("@livewire('notifications.notification-bell', [])", false);
});

test('candidates can save, apply once, withdraw, and retain an auditable application history', function (): void {
    $owner = User::factory()->create();
    $candidate = User::factory()->create();
    assignJobRole($candidate, 'candidate');
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company);
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);
    $job = $job->fresh();

    expect($candidate->hasRole('candidate'))->toBeTrue()->and($candidate->isActive())->toBeTrue()->and($candidate->hasVerifiedEmail())->toBeTrue()->and($job->acceptsApplications())->toBeTrue()->and($candidate->can('save', $job))->toBeTrue();
    expect(app(SaveJobForLater::class)->toggle($job, $candidate))->toBeTrue()
        ->and($candidate->savedJobs()->whereKey($job->id)->exists())->toBeTrue();
    Livewire::actingAs($candidate)->test(JobActions::class, ['job' => $job])
        ->set('coverLetter', 'I am excited to bring my Laravel experience to this team.')
        ->call('apply')
        ->assertRedirect(route('candidate.applications.index'))
        ->assertHasNoErrors();

    $application = JobApplication::query()->where('job_id', $job->id)->where('candidate_id', $candidate->id)->firstOrFail();
    expect($application->status)->toBe(JobApplicationStatus::Applied)
        ->and($application->statusChanges()->count())->toBe(1)
        ->and(AuditLog::query()->where('event', 'job.application_submitted')->exists())->toBeTrue();
    expect(fn () => app(ApplyToJob::class)->execute($job, $candidate, null))->toThrow(ValidationException::class);
    $this->actingAs($candidate)->get(route('candidate.applications.index'))->assertOk()->assertSee('Senior Laravel Engineer');
    $this->get(route('candidate.saved-jobs.index'))->assertOk()->assertSee('Senior Laravel Engineer');

    app(WithdrawJobApplication::class)->execute($application, $candidate);
    expect($application->fresh()->status)->toBe(JobApplicationStatus::Withdrawn)
        ->and($application->statusChanges()->count())->toBe(2)
        ->and(AuditLog::query()->where('event', 'job.application_withdrawn')->exists())->toBeTrue();
    expect(fn () => app(WithdrawJobApplication::class)->execute($application->fresh(), $candidate))->toThrow(AuthorizationException::class);
});

test('employers can progress applications through valid stages and candidates cannot access team inboxes', function (): void {
    $owner = User::factory()->create();
    $candidate = User::factory()->create();
    assignJobRole($candidate, 'candidate');
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company);
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);
    $application = app(ApplyToJob::class)->execute($job, $candidate, null);

    $review = app(ReviewJobApplication::class);
    foreach ([JobApplicationStatus::Reviewing, JobApplicationStatus::Shortlisted, JobApplicationStatus::Interview, JobApplicationStatus::Offer, JobApplicationStatus::Hired] as $status) {
        $application = $review->execute($application, $owner, $status, 'Reviewed by the hiring team.');
    }

    expect($application->status)->toBe(JobApplicationStatus::Hired)
        ->and($application->statusChanges()->count())->toBe(6)
        ->and(AuditLog::query()->where('event', 'job.application_status_changed')->count())->toBe(5);
    expect(fn () => $review->execute($application, $owner, JobApplicationStatus::Reviewing))->toThrow(ValidationException::class);

    $this->actingAs($candidate)->get(route('employer.jobs.applications', $job))->assertForbidden();
    $this->actingAs($owner)->get(route('employer.jobs.applications', $job))->assertOk()->assertSee('Applications')->assertSee($candidate->name);
});

test('employer job management is company-scoped even when a user has an employer capability', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company);
    $outsider = User::factory()->create();
    assignJobRole($outsider, 'employer');

    expect($outsider->can('manage', $job))->toBeFalse();
    Livewire::actingAs($outsider)->test(ManageJob::class, ['job' => $job])->assertForbidden();
});

test('jobs can be duplicated as drafts without copying applications or deadlines', function (): void {
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $job = createJobListing($owner, $company, ['application_deadline' => now()->addMonth()->toDateString()]);
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);
    $candidate = User::factory()->create();
    assignJobRole($candidate, 'candidate');
    app(ApplyToJob::class)->execute($job, $candidate, null);

    $copy = app(DuplicateJob::class)->execute($job, $owner);
    expect($copy->status)->toBe(JobStatus::Draft)
        ->and($copy->application_deadline)->toBeNull()
        ->and($copy->applications()->count())->toBe(0)
        ->and($copy->skills()->count())->toBe(3)
        ->and($copy->slug)->not->toBe($job->slug);
});

test('queued domain listeners notify only scoped hiring teammates and applicants', function (): void {
    Notification::fake();
    $owner = User::factory()->create();
    $company = createJobCompany($owner);
    $candidate = User::factory()->create();
    assignJobRole($candidate, 'candidate');
    $job = createJobListing($owner, $company);
    app(ChangeJobStatus::class)->execute($job, $owner, JobStatus::Published);
    $application = app(ApplyToJob::class)->execute($job, $candidate, null);

    (new NotifyCompanyOfJobApplication)->handle(new JobApplicationSubmitted($application->id));
    Notification::assertSentTo($owner, JobApplicationReceived::class);
    Notification::assertNotSentTo($candidate, JobApplicationReceived::class);

    app(ReviewJobApplication::class)->execute($application, $owner, JobApplicationStatus::Reviewing);
    (new NotifyCandidateOfApplicationStatus)->handle(new JobApplicationStatusChanged($application->id));
    Notification::assertSentTo($candidate, JobApplicationStatusUpdated::class);

    app(WithdrawJobApplication::class)->execute($application->fresh(), $candidate);
    (new NotifyCompanyOfApplicationWithdrawal)->handle(new JobApplicationWithdrawn($application->id));
    Notification::assertSentTo($owner, JobApplicationWithdrawnNotification::class);
});
