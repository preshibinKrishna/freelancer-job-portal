<?php

declare(strict_types=1);

use App\Domain\Recruitment\Actions\AddCandidateToPipeline;
use App\Domain\Recruitment\Actions\ClaimRecruitmentRequest;
use App\Domain\Recruitment\Actions\CreateRecruitmentRequest;
use App\Domain\Recruitment\Actions\MoveCandidatePipelineStage;
use App\Domain\Recruitment\Actions\ScheduleRecruitmentInterview;
use App\Domain\Recruitment\Actions\UpdateRecruitmentInterview;
use App\Enums\CandidatePipelineStage;
use App\Enums\RecruitmentInterviewStatus;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\WorkplaceType;
use App\Livewire\Recruitment\ManageRecruitmentRequest;
use App\Livewire\Recruitment\RecruiterDashboard;
use App\Livewire\Recruitment\RecruitmentRequestWorkspace;
use App\Models\Company;
use App\Models\FreelancerProfile;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
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

function recruiterActor(): User
{
    $user = User::factory()->create();
    $user->roles()->syncWithoutDetaching([Role::query()->where('name', 'recruiter')->value('id')]);

    return $user;
}

function publicCandidate(bool $public = true): User
{
    $candidate = User::factory()->create();
    $candidate->roles()->syncWithoutDetaching([Role::query()->where('name', 'candidate')->value('id')]);
    $profile = UserProfile::factory()->for($candidate)->create(['headline' => 'Senior platform engineer', 'visibility' => $public ? 'public' : 'private']);
    FreelancerProfile::query()->create(['user_profile_id' => $profile->id]);

    return $candidate;
}

/** @return array{User, Company, RecruitmentRequest} */
function recruitmentRequestFixture(int $openings = 1): array
{
    $employer = User::factory()->create();
    $company = Company::factory()->published()->create(['owner_id' => $employer->id]);
    $request = app(CreateRecruitmentRequest::class)->execute($employer, $company, [
        'position' => 'Senior platform engineer', 'number_of_openings' => $openings,
        'experience_min' => 5, 'experience_max' => 10, 'salary_min' => '90000', 'salary_max' => '130000', 'currency' => 'USD',
        'location_id' => null, 'workplace_type' => WorkplaceType::Remote, 'notice_period_days' => 30,
        'urgency' => 'high', 'description' => 'Build resilient services and guide the team through a measured platform modernization.',
        'skills' => ['PHP', 'Laravel', 'Cloud infrastructure'],
    ]);

    return [$employer, $company, $request];
}

function claimedRecruitmentRequest(int $openings = 1): array
{
    [$employer, $company, $request] = recruitmentRequestFixture($openings);
    $recruiter = recruiterActor();
    $request = app(ClaimRecruitmentRequest::class)->execute($request, $recruiter);

    return [$employer, $company, $request, $recruiter];
}

function advanceCandidateTo(RecruitmentPipelineEntry $entry, User $recruiter, CandidatePipelineStage $target): RecruitmentPipelineEntry
{
    $move = app(MoveCandidatePipelineStage::class);
    $stages = [CandidatePipelineStage::Sourcing, CandidatePipelineStage::Screening, CandidatePipelineStage::Shortlisted, CandidatePipelineStage::ClientReview];
    foreach ($stages as $stage) {
        if ($entry->stage === $target) {
            break;
        }
        $entry = $move->execute($entry, $recruiter, $stage);
    }

    return $entry;
}

test('employers submit scoped recruitment assistance requests with the full role specification', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->published()->create(['owner_id' => $employer->id]);

    Livewire::actingAs($employer)->test(ManageRecruitmentRequest::class)
        ->set('companyId', (string) $company->id)
        ->set('position', 'Senior platform engineer')
        ->set('numberOfOpenings', '2')
        ->set('experienceMin', '5')->set('experienceMax', '10')
        ->set('salaryMin', '90000')->set('salaryMax', '130000')->set('currency', 'USD')
        ->set('workplaceType', 'remote')->set('noticePeriodDays', '30')
        ->set('urgency', 'urgent')->set('skillsInput', 'PHP, Laravel, Cloud')
        ->set('description', 'Build resilient services and guide the team through a measured platform modernization.')
        ->call('submit')->assertHasNoErrors()->assertRedirect();

    $request = RecruitmentRequest::query()->firstOrFail();
    expect($request->status)->toBe(RecruitmentRequestStatus::Submitted)
        ->and($request->company_id)->toBe($company->id)
        ->and($request->created_by)->toBe($employer->id)
        ->and($request->number_of_openings)->toBe(2)
        ->and($request->currency)->toBe('USD')
        ->and($request->skills)->toHaveCount(3)
        ->and(DB::table('recruitment_activities')->where('recruitment_request_id', $request->id)->count())->toBe(1);
    $this->actingAs($employer)->get(route('employer.recruitment-requests.index'))->assertOk()->assertSee($request->position);

    $outsider = User::factory()->create();
    expect(Gate::forUser($outsider)->allows('view', $request))->toBeFalse();
    $this->actingAs($outsider)->get(route('recruitment.requests.show', $request))->assertForbidden();
});

test('recruiters can claim an unassigned request and another recruiter cannot access it afterward', function (): void {
    [, , $request, $recruiter] = claimedRecruitmentRequest();
    $otherRecruiter = recruiterActor();

    expect($request->status)->toBe(RecruitmentRequestStatus::Active)
        ->and($request->assigned_recruiter_id)->toBe($recruiter->id)
        ->and(Gate::forUser($recruiter)->allows('managePipeline', $request))->toBeTrue()
        ->and(Gate::forUser($otherRecruiter)->allows('view', $request))->toBeFalse();

    $dashboard = Livewire::actingAs($recruiter)->test(RecruiterDashboard::class)->assertSee('Recruiter dashboard')->assertSee($request->position);
    expect($dashboard->viewData('activeRequisitions'))->toBe(1)
        ->and($dashboard->viewData('pendingActionCount'))->toBe(0);
    $this->actingAs($otherRecruiter)->get(route('recruitment.requests.show', $request))->assertForbidden();
    expect(fn () => app(ClaimRecruitmentRequest::class)->execute($request, $otherRecruiter))->toThrow(AuthorizationException::class);
});

test('only public candidate profiles can be associated and the company decides at Client Review', function (): void {
    [$employer, , $request, $recruiter] = claimedRecruitmentRequest();
    $candidate = publicCandidate();
    $privateCandidate = publicCandidate(false);
    $entry = app(AddCandidateToPipeline::class)->execute($request, $candidate, $recruiter);

    expect($entry->stage)->toBe(CandidatePipelineStage::New)
        ->and(Gate::forUser($employer)->allows('view', $entry))->toBeTrue();
    expect(fn () => app(AddCandidateToPipeline::class)->execute($request, $privateCandidate, $recruiter))->toThrow(ValidationException::class);
    expect(fn () => app(AddCandidateToPipeline::class)->execute($request, $candidate, $recruiter))->toThrow(ValidationException::class);

    $entry = advanceCandidateTo($entry, $recruiter, CandidatePipelineStage::ClientReview);
    expect(fn () => app(MoveCandidatePipelineStage::class)->execute($entry, $recruiter, CandidatePipelineStage::Interview))
        ->toThrow(AuthorizationException::class);

    $otherEmployer = User::factory()->create();
    expect(fn () => app(MoveCandidatePipelineStage::class)->execute($entry, $otherEmployer, CandidatePipelineStage::Interview))
        ->toThrow(AuthorizationException::class);
    $entry = app(MoveCandidatePipelineStage::class)->execute($entry, $employer, CandidatePipelineStage::Interview);
    expect($entry->stage)->toBe(CandidatePipelineStage::Interview);

    Livewire::actingAs($employer)->test(RecruitmentRequestWorkspace::class, ['recruitmentRequest' => $request])
        ->assertSee('Candidate pipeline')->assertSee('Senior platform engineer')->assertDontSee('Schedule interview');
});

test('recruiters schedule scoped interviews and placements fill a requisition at its opening count', function (): void {
    [, , $request, $recruiter] = claimedRecruitmentRequest(2);
    $candidate = publicCandidate();
    $entry = app(AddCandidateToPipeline::class)->execute($request, $candidate, $recruiter);
    $entry = advanceCandidateTo($entry, $recruiter, CandidatePipelineStage::ClientReview);
    $entry = app(MoveCandidatePipelineStage::class)->execute($entry, $request->creator, CandidatePipelineStage::Interview);

    $interview = app(ScheduleRecruitmentInterview::class)->execute($entry, $recruiter, [
        'title' => 'Technical interview', 'starts_at' => now()->addDays(2)->toDateTimeString(),
        'ends_at' => now()->addDays(2)->addHour()->toDateTimeString(), 'location_or_link' => 'https://meet.example.test/room',
        'private_notes' => 'Assess systems design and operational tradeoffs.',
    ]);
    expect($interview->pipelineEntry->is($entry))->toBeTrue();

    $otherRecruiter = recruiterActor();
    expect(fn () => app(ScheduleRecruitmentInterview::class)->execute($entry, $otherRecruiter, [
        'title' => 'Unauthorized interview', 'starts_at' => now()->addDays(3)->toDateTimeString(),
        'ends_at' => now()->addDays(3)->addHour()->toDateTimeString(), 'location_or_link' => null, 'private_notes' => null,
    ]))->toThrow(AuthorizationException::class);

    $completedInterview = app(UpdateRecruitmentInterview::class)->execute($interview, $recruiter, RecruitmentInterviewStatus::Completed);
    expect($completedInterview->status)->toBe(RecruitmentInterviewStatus::Completed);
    expect(fn () => app(UpdateRecruitmentInterview::class)->execute($completedInterview, $recruiter, RecruitmentInterviewStatus::Cancelled))
        ->toThrow(ValidationException::class);

    $move = app(MoveCandidatePipelineStage::class);
    $entry = $move->execute($entry, $recruiter, CandidatePipelineStage::Offer);
    $entry = $move->execute($entry, $recruiter, CandidatePipelineStage::Placed);
    expect($entry->stage)->toBe(CandidatePipelineStage::Placed)
        ->and($request->fresh()->status)->toBe(RecruitmentRequestStatus::Active);

    $secondCandidate = publicCandidate();
    $second = app(AddCandidateToPipeline::class)->execute($request->fresh(), $secondCandidate, $recruiter);
    $second = advanceCandidateTo($second, $recruiter, CandidatePipelineStage::ClientReview);
    $second = app(MoveCandidatePipelineStage::class)->execute($second, $request->creator, CandidatePipelineStage::Interview);
    $second = $move->execute($second, $recruiter, CandidatePipelineStage::Offer);
    $second = $move->execute($second, $recruiter, CandidatePipelineStage::Placed);
    expect($request->fresh()->status)->toBe(RecruitmentRequestStatus::Filled)
        ->and(DB::table('recruitment_pipeline_stage_changes')->where('pipeline_entry_id', $second->id)->count())->toBe(8)
        ->and(DB::table('recruitment_activities')->where('recruitment_request_id', $request->id)->exists())->toBeTrue();
});

test('recruiters cannot skip pipeline stages or move terminal candidates', function (): void {
    [, , $request, $recruiter] = claimedRecruitmentRequest();
    $entry = app(AddCandidateToPipeline::class)->execute($request, publicCandidate(), $recruiter);

    expect(fn () => app(MoveCandidatePipelineStage::class)->execute($entry, $recruiter, CandidatePipelineStage::Interview))
        ->toThrow(ValidationException::class);
    $entry = app(MoveCandidatePipelineStage::class)->execute($entry, $recruiter, CandidatePipelineStage::Rejected);
    expect($entry->stage)->toBe(CandidatePipelineStage::Rejected);
    expect(fn () => app(MoveCandidatePipelineStage::class)->execute($entry, $recruiter, CandidatePipelineStage::New))
        ->toThrow(ValidationException::class);
});
