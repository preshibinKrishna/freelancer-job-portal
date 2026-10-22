<?php

declare(strict_types=1);

use App\Domain\Profiles\Services\ProfileCompleteness;
use App\Livewire\Profiles\ManageTalentProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ProfileResume;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

function assignProfileRole(User $user): void
{
    $user->roles()->syncWithoutDetaching([Role::query()->where('name', 'candidate')->firstOrFail()->id]);
}

test('candidates can edit professional profile and save normalized career information', function (): void {
    $candidate = User::factory()->create();
    assignProfileRole($candidate);

    Livewire::actingAs($candidate)->test(ManageTalentProfile::class)
        ->set('name', 'Jordan Rivera')
        ->set('headline', 'Senior backend engineer')
        ->set('bio', 'I build reliable software and mentor product teams.')
        ->set('skillsInput', 'PHP, Laravel, MySQL')
        ->set('availability', 'open_to_offers')
        ->set('preferredWorkTypes', ['remote', 'hybrid'])
        ->set('visibility', 'public')
        ->set('experiences.0.title', 'Software Engineer')
        ->set('experiences.0.company', 'Northstar')
        ->set('experiences.0.started_at', '2021-03-01')
        ->set('educations.0.institution', 'State University')
        ->set('educations.0.degree', 'BSc')
        ->set('languages.0.language', 'English')
        ->set('portfolioLinks.0.title', 'Selected work')
        ->set('portfolioLinks.0.url', 'https://portfolio.example.test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('candidate.profile.edit'));

    $profile = $candidate->fresh()->profile;
    expect($profile)->not->toBeNull()
        ->and($profile->visibility)->toBe('public')
        ->and($profile->profile_slug)->toContain('jordan-rivera')
        ->and($profile->skills->pluck('name')->all())->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($profile->experiences()->first()->company)->toBe('Northstar')
        ->and($profile->educations()->first()->institution)->toBe('State University')
        ->and($profile->portfolioLinks()->first()->url)->toBe('https://portfolio.example.test');
});

test('public profile page is opt in and never exposes salary preferences or resume data', function (): void {
    $candidate = User::factory()->create(['name' => 'Avery Morgan']);
    assignProfileRole($candidate);
    $private = $candidate->profile()->create(['headline' => 'Private engineer', 'visibility' => 'private', 'expected_salary' => '180000', 'expected_salary_currency' => 'USD']);
    $this->get(route('talent.show', 'private-engineer'))->assertNotFound();

    $private->update(['visibility' => 'public', 'profile_slug' => 'avery-morgan-profile']);
    $private->resumes()->create(['disk' => 'local', 'path' => 'talent/private/cv.pdf', 'original_filename' => 'confidential-resume.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 100, 'scan_status' => 'pending', 'uploaded_at' => now()]);

    $this->get(route('talent.show', $private->profile_slug))
        ->assertOk()
        ->assertSee('Avery Morgan')
        ->assertSee('Private engineer')
        ->assertDontSee('180000')
        ->assertDontSee('confidential-resume.pdf');
});

test('candidate resume is stored on private disk and only its owner can download before scanning', function (): void {
    Storage::fake('local');
    $candidate = User::factory()->create();
    assignProfileRole($candidate);

    Livewire::actingAs($candidate)->test(ManageTalentProfile::class)
        ->set('resumeUpload', UploadedFile::fake()->createWithContent('candidate-resume.pdf', "%PDF-1.4\nCandidate CV\n"))
        ->call('uploadResume')
        ->assertHasNoErrors();

    $resume = ProfileResume::query()->firstOrFail();
    expect($resume->scan_status)->toBe('pending')->and($resume->path)->not->toStartWith('/');
    Storage::disk('local')->assertExists($resume->path);

    $this->actingAs($candidate)->get(route('candidate.resumes.download', $resume))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $other = User::factory()->create();
    $this->actingAs($other)->get(route('candidate.resumes.download', $resume))->assertForbidden();

    $employer = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);
    $job = Job::factory()->create(['company_id' => $company->id, 'created_by' => $employer->id]);
    JobApplication::factory()->create(['candidate_id' => $candidate->id, 'job_id' => $job->id]);
    $this->actingAs($employer)->get(route('candidate.resumes.download', $resume))->assertForbidden();
    $resume->update(['scan_status' => 'clean']);
    $this->get(route('candidate.resumes.download', $resume))->assertOk();

    Livewire::actingAs($candidate)->test(ManageTalentProfile::class)
        ->call('removeResume', $resume->public_id)
        ->assertHasNoErrors();
    expect(ProfileResume::query()->whereKey($resume->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($resume->path);
});

test('profile photo is privately stored and hidden when profile is private', function (): void {
    Storage::fake('local');
    $candidate = User::factory()->create();
    assignProfileRole($candidate);
    $component = Livewire::actingAs($candidate)->test(ManageTalentProfile::class)
        ->set('photoUpload', UploadedFile::fake()->image('portrait.png'))
        ->call('savePhoto')
        ->assertHasNoErrors();

    $profile = $candidate->fresh()->profile;
    Storage::disk('local')->assertExists($profile->photo_path);
    $other = User::factory()->create();
    $this->actingAs($other)->get(route('talent.photo', $candidate))->assertForbidden();
    $this->actingAs($candidate)->get(route('talent.photo', $candidate))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    $profile->update(['visibility' => 'public', 'profile_slug' => 'public-candidate']);
    $this->get(route('talent.photo', $candidate))->assertOk();
});

test('profile completion gives concrete recommendations and reaches full completion', function (): void {
    $candidate = User::factory()->create();
    assignProfileRole($candidate);
    $completeness = app(ProfileCompleteness::class);
    $initial = $completeness->assess(null);
    expect($initial['percentage'])->toBe(0)->and($initial['recommendations'])->toContain('Add your work experience', 'Add a resume');

    $profile = $candidate->profile()->create(['headline' => 'Engineer', 'bio' => 'I build software.', 'visibility' => 'private', 'location_id' => null, 'photo_path' => 'private/photo.webp', 'availability' => 'open_to_offers', 'preferred_work_types' => ['remote'], 'github_url' => 'https://github.com/example']);
    foreach (['PHP', 'Laravel', 'MySQL'] as $name) {
        $profile->skills()->attach(Skill::query()->create(['name' => $name, 'slug' => strtolower($name)]));
    }
    $profile->experiences()->create(['title' => 'Engineer', 'company' => 'Acme']);
    $profile->educations()->create(['institution' => 'University']);
    $profile->portfolioLinks()->create(['title' => 'Portfolio', 'url' => 'https://portfolio.example.test']);
    $profile->resumes()->create(['disk' => 'local', 'path' => 'r.pdf', 'original_filename' => 'r.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 1, 'uploaded_at' => now()]);
    expect($completeness->assess($profile->fresh())['percentage'])->toBe(80);
});
