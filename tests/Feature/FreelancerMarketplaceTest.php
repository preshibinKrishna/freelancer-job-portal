<?php

declare(strict_types=1);

use App\Domain\Freelancers\Actions\EnableFreelancerProfile;
use App\Enums\FreelancerReviewStatus;
use App\Enums\FreelancerVerificationStatus;
use App\Enums\LocationType;
use App\Livewire\Freelancers\SearchFreelancers;
use App\Models\Company;
use App\Models\FreelancerProfile;
use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use App\Models\Location;
use App\Models\ProjectCategory;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\IdentityAccessSeeder;
use Database\Seeders\ProjectCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([IdentityAccessSeeder::class, ProjectCategorySeeder::class]);
});

function createListedFreelancer(string $name, array $profileAttributes = [], array $freelancerAttributes = []): array
{
    $user = User::factory()->create(['name' => $name]);
    $talent = UserProfile::factory()->for($user)->create([
        'profile_slug' => Str::slug($name).'-'.strtolower(substr($user->public_id, -8)),
        'visibility' => 'public',
        ...$profileAttributes,
    ]);
    $freelancer = FreelancerProfile::factory()->for($talent, 'talentProfile')->listed()->create($freelancerAttributes);

    return [$user, $talent, $freelancer];
}

test('candidate can enable freelancer capability without losing candidate capabilities', function (): void {
    $candidate = User::factory()->create();
    $candidate->roles()->attach(Role::query()->where('name', 'candidate')->value('id'));

    $freelancer = app(EnableFreelancerProfile::class)->execute($candidate);

    expect($candidate->fresh()->hasRole('candidate'))->toBeTrue()
        ->and($candidate->fresh()->hasRole('freelancer'))->toBeTrue()
        ->and($freelancer->talentProfile->user_id)->toBe($candidate->id);
});

test('only listed public freelancer profiles are discoverable and the owner can view an unlisted profile', function (): void {
    [$listedUser, , $listed] = createListedFreelancer('Jordan Lee');
    [, , $unlisted] = createListedFreelancer('Private Lee', ['visibility' => 'private'], ['is_listed' => false]);

    expect($listed->newQuery()->discoverable()->whereKey($listed->id)->exists())->toBeTrue()
        ->and($listed->newQuery()->discoverable()->whereKey($unlisted->id)->exists())->toBeFalse();

    $this->get(route('freelancers.show', $unlisted->talentProfile->profile_slug))->assertNotFound();
    $this->actingAs($unlisted->talentProfile->user)->get(route('freelancers.show', $unlisted->talentProfile->profile_slug))->assertOk();
    expect(Gate::forUser($listedUser)->allows('view', $listed))->toBeTrue();
});

test('freelancer portfolio page presents professional work and hides private salary and resume data', function (): void {
    [$user, $talent, $freelancer] = createListedFreelancer('Taylor Morgan', [
        'headline' => 'Staff platform engineer',
        'bio' => 'I design and build dependable data platforms for growing teams.',
        'expected_salary' => '220000',
        'expected_salary_currency' => 'USD',
        'availability' => 'open_to_offers',
        'experience_years' => 9,
        'github_url' => 'https://github.com/taylor',
    ], [
        'hourly_rate' => 125,
        'project_rate' => 12000,
        'currency' => 'USD',
        'weekly_capacity' => 24,
        'verification_status' => FreelancerVerificationStatus::Verified,
    ]);
    $talent->skills()->attach(Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']));
    $talent->experiences()->create(['title' => 'Platform Engineer', 'company' => 'Northstar', 'description' => 'Built the event platform.', 'is_current' => true]);
    $talent->educations()->create(['institution' => 'State University', 'degree' => 'BSc']);
    $talent->certifications()->create(['name' => 'Cloud Architect', 'issuer' => 'Example Cloud']);
    $talent->languages()->create(['language' => 'English', 'proficiency' => 'Professional']);
    $talent->portfolioLinks()->create(['title' => 'Engineering notes', 'url' => 'https://portfolio.example.test']);
    $category = ProjectCategory::query()->firstOrFail();
    $freelancer->categories()->attach($category);
    FreelancerProject::factory()->for($freelancer)->create([
        'project_category_id' => $category->id,
        'title' => 'Event processing platform',
        'summary' => 'Designed a resilient event processing platform that improved delivery reliability.',
        'verification_status' => 'verified',
    ]);
    $talent->resumes()->create(['disk' => 'local', 'path' => 'private/cv.pdf', 'original_filename' => 'confidential-cv.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 100, 'scan_status' => 'pending', 'uploaded_at' => now()]);

    $this->get(route('freelancers.show', $talent->profile_slug))
        ->assertOk()
        ->assertSee('Taylor Morgan')
        ->assertSee('Staff platform engineer')
        ->assertSee('Technology & skills', false)
        ->assertSee('Event processing platform')
        ->assertSee('Northstar')
        ->assertSee('Cloud Architect')
        ->assertSee('English')
        ->assertSee('M Talent verified freelancer')
        ->assertDontSee('220000')
        ->assertDontSee('confidential-cv.pdf')
        ->assertDontSee($user->email);
});

test('freelancer search filters listed profiles by skill, location, rates, experience, availability, verification, and category', function (): void {
    $location = Location::factory()->ofType(LocationType::City)->create(['name' => 'Northstar City']);
    [$matchingUser, $matchingTalent, $matching] = createListedFreelancer('Morgan Chen', [
        'location_id' => $location->id,
        'availability' => 'actively_looking',
        'experience_years' => 8,
    ], ['hourly_rate' => 95, 'currency' => 'USD', 'verification_status' => FreelancerVerificationStatus::Verified]);
    $skill = Skill::factory()->create(['name' => 'PHP', 'slug' => 'php']);
    $matchingTalent->skills()->attach($skill);
    $category = ProjectCategory::query()->firstOrFail();
    $matching->categories()->attach($category);
    $project = FreelancerProject::factory()->for($matching)->create([
        'project_category_id' => $category->id,
        'verification_status' => 'verified',
    ]);
    FreelancerReview::query()->create([
        'freelancer_project_id' => $project->id,
        'reviewer_id' => User::factory()->create()->id,
        'company_id' => Company::factory()->create()->id,
        'rating' => 5,
        'review' => 'Excellent delivery and clear communication throughout the engagement.',
        'moderation_status' => FreelancerReviewStatus::Approved,
    ]);
    $other = createListedFreelancer('Alex Kim', ['availability' => 'not_looking', 'experience_years' => 1], ['hourly_rate' => 35, 'currency' => 'USD']);

    $search = new SearchFreelancers;
    $search->skill = 'php';
    $search->location = $location->public_id;
    $search->hourlyMin = '80';
    $search->hourlyMax = '120';
    $search->currency = 'USD';
    $search->experience = '5';
    $search->availability = 'actively_looking';
    $search->rating = '4.5';
    $search->verified = 'yes';
    $search->category = $category->slug;
    $results = $search->render()->getData()['freelancers'];

    expect($results->total())->toBe(1)
        ->and($results->first()->talentProfile->user->name)->toBe($matchingUser->name)
        ->and($results->first()->talentProfile->user->name)->not->toBe($other[0]->name);
    $this->get(route('freelancers.index'))->assertOk();
});

test('freelancer profiles are protected from edits by other users', function (): void {
    [$owner, , $freelancer] = createListedFreelancer('Owner Profile');
    $other = User::factory()->create();

    expect(Gate::forUser($owner)->allows('update', $freelancer))->toBeTrue()
        ->and(Gate::forUser($other)->allows('update', $freelancer))->toBeFalse()
        ->and(Gate::forUser($other)->allows('requestVerification', $freelancer))->toBeFalse();
});
