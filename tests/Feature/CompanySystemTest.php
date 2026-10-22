<?php

declare(strict_types=1);

use App\Domain\Companies\Actions\SaveCompanyProfile;
use App\Enums\CompanyMemberRole;
use App\Enums\CompanyVerificationStatus;
use App\Livewire\Companies\ManageCompany;
use App\Livewire\Companies\ReviewCompanyVerifications;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\CompanyVerification;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\GeographicLocationSeeder;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
    $this->seed(GeographicLocationSeeder::class);
    $this->seed(CompanySeeder::class);
});

function createOwnedCompany(User $owner, array $overrides = []): Company
{
    return app(SaveCompanyProfile::class)->create($owner, array_merge([
        'name' => 'Northstar Systems',
        'industry' => 'Software Development',
        'description' => 'We build reliable tools for teams.',
        'website' => 'https://northstar.example',
        'company_size' => '11-50',
        'founded_year' => '2018',
        'headquarters_location_id' => Location::query()->where('name', 'Kochi')->value('id'),
        'it_park_id' => Location::query()->where('name', 'Infopark')->value('id'),
        'office_location_ids' => [],
        'social_links' => [],
        'benefits' => ['Flexible work'],
        'technology_stack' => ['PHP', 'Laravel'],
        'company_culture' => 'Curious and collaborative.',
        'contact_name' => 'Private Contact',
        'contact_email' => 'private@northstar.example',
        'contact_phone' => '+1 555 0100',
        'published' => true,
        'logo_path' => null,
        'cover_image_path' => null,
    ], $overrides));
}

test('a user can create multiple companies and becomes each company owner and employer', function (): void {
    $owner = User::factory()->create();
    $first = createOwnedCompany($owner);
    $second = createOwnedCompany($owner, ['name' => 'Northstar Labs']);

    expect($owner->fresh()->hasRole('candidate'))->toBeFalse()
        ->and($owner->fresh()->hasRole('employer'))->toBeTrue()
        ->and($first->owner->is($owner))->toBeTrue()
        ->and($owner->fresh()->ownedCompanies()->count())->toBe(2)
        ->and($first->memberships()->where('role', CompanyMemberRole::Owner)->exists())->toBeTrue();
});

test('company admins can add and remove active verified employer team members', function (): void {
    $owner = User::factory()->create();
    $company = createOwnedCompany($owner);
    $member = User::factory()->create(['email' => 'recruiter@example.test']);

    Livewire::actingAs($owner)->test(ManageCompany::class, ['company' => $company])
        ->set('memberEmail', $member->email)
        ->set('memberRole', CompanyMemberRole::Recruiter->value)
        ->call('addMember')
        ->assertHasNoErrors();

    $membership = CompanyMembership::query()->where('company_id', $company->id)->where('user_id', $member->id)->firstOrFail();
    expect($membership->role)->toBe(CompanyMemberRole::Recruiter)
        ->and($member->fresh()->hasRole('employer'))->toBeTrue()
        ->and($company->fresh()->members()->whereKey($member->id)->exists())->toBeTrue();

    Livewire::actingAs($owner)->test(ManageCompany::class, ['company' => $company])
        ->call('removeMember', $member->id)
        ->assertHasNoErrors();
    expect(CompanyMembership::query()->where('company_id', $company->id)->where('user_id', $member->id)->exists())->toBeFalse();
});

test('company membership is required for profile management even when a user has the employer role', function (): void {
    $owner = User::factory()->create();
    $company = createOwnedCompany($owner);
    $outsider = User::factory()->create();
    $outsider->roles()->attach(Role::query()->where('name', 'employer')->firstOrFail());

    expect($outsider->can('update', $company))->toBeFalse();
    Livewire::actingAs($outsider)->test(ManageCompany::class, ['company' => $company])->assertForbidden();
});

test('company profile Livewire form saves a public brand profile and media', function (): void {
    Storage::fake('public');
    $owner = User::factory()->create();

    $component = Livewire::actingAs($owner)->test(ManageCompany::class)
        ->set('name', 'Brightline Engineering')
        ->set('industry', 'Technology')
        ->set('description', 'A calm, talented product engineering team.')
        ->set('companySize', '11-50')
        ->set('headquartersLocationId', Location::query()->where('name', 'Kochi')->value('id'))
        ->set('itParkId', Location::query()->where('name', 'Infopark')->value('id'))
        ->set('officeLocationIds', [Location::query()->where('name', 'Infopark')->value('id')])
        ->set('published', true)
        ->set('socialLinks.linkedin', 'https://www.linkedin.com/company/brightline')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->set('coverImage', UploadedFile::fake()->image('cover.png'))
        ->call('save')
        ->assertHasNoErrors();

    $company = Company::query()->where('slug', 'brightline-engineering')->firstOrFail();
    expect($company->owner_id)->toBe($owner->id)
        ->and($company->itPark->name)->toBe('Infopark')
        ->and($company->officeLocations()->whereKey($company->it_park_id)->exists())->toBeTrue()
        ->and($company->social_links['linkedin'])->toBe('https://www.linkedin.com/company/brightline');
    Storage::disk('public')->assertExists($company->logo_path);
    Storage::disk('public')->assertExists($company->cover_image_path);
    $component->assertRedirect(route('employer.companies.edit', $company));
});

test('public company pages show approved public details but never private contact or reviewer notes', function (): void {
    $company = Company::factory()->published()->verified()->create([
        'name' => 'Public Northstar', 'slug' => 'public-northstar', 'contact_email' => 'private@northstar.example',
        'contact_phone' => '+1 555 0100', 'description' => 'A considered engineering studio.',
        'technology_stack' => ['PHP', 'Laravel'],
    ]);
    CompanyVerification::factory()->create([
        'company_id' => $company->id, 'status' => CompanyVerificationStatus::Verified,
        'reviewer_notes' => 'Internal-only review note',
    ]);

    $this->get(route('companies.show', $company->slug))->assertOk()
        ->assertSee('Verified company')
        ->assertSee('A considered engineering studio.')
        ->assertSee('Laravel')
        ->assertDontSee('private@northstar.example')
        ->assertDontSee('+1 555 0100')
        ->assertDontSee('Internal-only review note');
});

test('company verification is reviewed by authorized staff', function (): void {
    $owner = User::factory()->create();
    $company = createOwnedCompany($owner);
    Livewire::actingAs($owner)->test(ManageCompany::class, ['company' => $company])
        ->set('verificationNote', 'Our business registration details are ready for review.')
        ->call('submitVerification')
        ->assertHasNoErrors();

    $verification = CompanyVerification::query()->where('company_id', $company->id)->firstOrFail();
    expect($company->fresh()->verification_status)->toBe(CompanyVerificationStatus::Pending);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $this->actingAs($admin)->get(route('admin.company-verifications.index'))->assertOk()->assertSee('Northstar Systems');
    Livewire::actingAs($admin)->test(ReviewCompanyVerifications::class)
        ->call('review', $verification->public_id, CompanyVerificationStatus::Verified->value)
        ->assertStatus(200)
        ->assertHasNoErrors();

    expect($company->fresh()->verification_status)->toBe(CompanyVerificationStatus::Verified)
        ->and($verification->fresh()->reviewer->is($admin))->toBeTrue();
    $owner->roles()->attach(Role::query()->where('name', 'candidate')->firstOrFail());
    $this->actingAs($owner)->get(route('admin.company-verifications.index'))->assertForbidden();
});

test('company workspace pages render accessible Livewire interfaces', function (): void {
    $owner = User::factory()->create();
    $company = createOwnedCompany($owner);

    $this->actingAs($owner)->get(route('employer.companies.index'))->assertOk()->assertSee('Your companies');
    $this->get(route('employer.companies.create'))->assertOk()->assertSee('Company name')->assertSee('Company culture');
    $this->get(route('employer.companies.edit', $company))->assertOk()->assertSee('Company team')->assertSee('Company verification');
});

test('unpublished company pages are unavailable to guests', function (): void {
    $company = Company::factory()->create(['slug' => 'draft-northstar']);

    $this->get(route('companies.show', $company->slug))->assertNotFound();
});
