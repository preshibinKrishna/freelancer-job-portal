<?php

declare(strict_types=1);

use App\Models\FreelancerProfile;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\GeographicLocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('robots and sitemap endpoints are dynamic and exclude private profiles', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $profile = UserProfile::factory()->for($user)->create(['profile_slug' => 'private-profile', 'visibility' => 'private']);
    FreelancerProfile::factory()->create(['user_profile_id' => $profile->id, 'is_listed' => false]);

    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /candidate/', false)->assertSee(route('sitemap'), false);
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertDontSee('private-profile');
    $this->actingAs($user)->get('/profile')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
});

test('private freelancer pages are never marked indexable', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create(['profile_slug' => 'private-freelancer', 'visibility' => 'private']);
    FreelancerProfile::factory()->create(['user_profile_id' => $profile->id, 'is_listed' => false]);

    $this->actingAs($user)->get('/freelancers/private-freelancer')
        ->assertOk()
        ->assertSee('name="robots" content="noindex,follow"', false)
        ->assertDontSee('"@type":"Person"', false);
});

test('thin skill pages remain available but noindex until enough public listings exist', function (): void {
    $skill = Skill::factory()->create(['name' => 'Rare Framework', 'slug' => 'rare-framework']);

    $this->get(route('skills.show', $skill->slug))
        ->assertOk()
        ->assertSee('name="robots" content="noindex,follow"', false)
        ->assertSee('Rare Framework jobs, freelance projects, and developers');
});

test('location landing pages render hierarchy and stay out of the index without enough public results', function (): void {
    $this->seed(GeographicLocationSeeder::class);
    $location = Location::query()->where('name', 'Technopark')->firstOrFail();

    $this->get(route('locations.show', ['path' => $location->path()]))
        ->assertOk()
        ->assertSee('Technopark jobs and technology talent')
        ->assertSee('BreadcrumbList', false)
        ->assertSee('name="robots" content="noindex,follow"', false);
});
