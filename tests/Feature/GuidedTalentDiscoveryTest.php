<?php

declare(strict_types=1);

use App\Livewire\Recruitment\ManageTalentRequirement;
use App\Models\Company;
use App\Models\Skill;
use App\Models\TalentRequirementRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function publicProfessional(string $name, array $skills, string $visibility = 'public'): UserProfile
{
    $user = User::factory()->create(['name' => $name]);
    $profile = UserProfile::factory()->for($user)->create([
        'profile_slug' => str($name)->slug().'-'.str()->random(6),
        'visibility' => $visibility,
        'experience_years' => 6,
    ]);
    $profile->skills()->sync(collect($skills)->map(fn (string $skill): int => Skill::query()->firstOrCreate(['slug' => str($skill)->slug()], ['name' => $skill])->id));

    return $profile;
}

function ecommerceAnswers(): array
{
    return [
        'project_stage' => 'planning',
        'delivery_scope' => 'design_build',
        'timeline' => '1_3_months',
        'budget_range' => '5k_20k',
        'commerce_model' => 'b2c',
        'catalog_size' => '100_1000',
        'payments' => 'online',
        'fulfilment' => 'shipping',
    ];
}

test('employer can complete a structured ecommerce brief and receives relevant public professionals', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);
    $matched = publicProfessional('Relevant Professional', ['E-commerce', 'Payment Integration']);
    publicProfessional('Private Professional', ['E-commerce', 'Payment Integration'], 'private');
    publicProfessional('Unrelated Professional', ['Graphic Design']);

    $component = Livewire::actingAs($employer)->test(ManageTalentRequirement::class)
        ->assertSee('Describe the solution you need')
        ->set('category', 'website')
        ->set('solutionType', 'ecommerce')
        ->set('companyId', (string) $company->id)
        ->set('answers', ecommerceAnswers())
        ->set('selectedSkills', ['E-commerce', 'Payment Integration'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect();

    $request = TalentRequirementRequest::query()->firstOrFail();
    expect($request->category)->toBe('website')
        ->and($request->solution_type)->toBe('ecommerce')
        ->and($request->details)->toBe(ecommerceAnswers())
        ->and($request->suggested_skills)->toBe(['E-commerce', 'Payment Integration'])
        ->and($request->company_id)->toBe($company->id)
        ->and(DB::table('audit_logs')->where('event', 'talent_requirement.submitted')->exists())->toBeTrue();

    $this->actingAs($employer)->get(route('employer.talent-requirements.matches', $request))
        ->assertOk()->assertSee('Recommended professionals')->assertSee($matched->user->name)
        ->assertDontSee('Private Professional')->assertDontSee('Unrelated Professional')
        ->assertSee('E-commerce website')->assertSee('Online payment processing');
    expect($component->instance()->requestPublicId)->toBeNull();
});

test('solution selection exposes professional type-specific questions and discards stale answers', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);

    Livewire::actingAs($employer)->test(ManageTalentRequirement::class)
        ->set('category', 'website')
        ->set('solutionType', 'ecommerce')
        ->assertSee('Commerce model')->assertSee('Approximate catalogue size')
        ->set('answers.commerce_model', 'b2c')
        ->set('category', 'business_system')
        ->assertDontSee('Commerce model')
        ->set('solutionType', 'erp')
        ->assertSee('Enterprise resource planning (ERP)')
        ->assertSee('Modules required')
        ->set('companyId', (string) $company->id)
        ->set('answers', ['modules' => 'finance'])
        ->call('submit')->assertHasErrors(['answers']);
});

test('talent requirement matches are restricted to the company team', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);
    $request = TalentRequirementRequest::factory()->create(['company_id' => $company->id, 'created_by' => $employer->id]);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('employer.talent-requirements.matches', $request))->assertForbidden();
    expect(Gate::forUser($other)->allows('view', $request))->toBeFalse();
});

test('requirements reject arbitrary catalog values and unapproved skills', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $employer->id]);

    Livewire::actingAs($employer)->test(ManageTalentRequirement::class)
        ->set('companyId', (string) $company->id)
        ->set('category', 'website')
        ->set('solutionType', 'ecommerce')
        ->set('answers', ecommerceAnswers())
        ->set('selectedSkills', ['E-commerce', 'Unapproved capability'])
        ->call('submit')->assertHasErrors(['selectedSkills.*']);
});
