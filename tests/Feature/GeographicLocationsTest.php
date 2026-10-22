<?php

declare(strict_types=1);

use App\Domain\Geography\Actions\SaveLocation;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\GeographicLocationSeeder;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
    $this->seed(GeographicLocationSeeder::class);
});

test('reference locations seed idempotently as a reusable hierarchy', function (): void {
    $this->seed(GeographicLocationSeeder::class);

    $india = Location::query()->where('country_code', 'IN')->firstOrFail();
    $kerala = Location::query()->where('name', 'Kerala')->firstOrFail();

    expect($india->type)->toBe(LocationType::Country)
        ->and($kerala->parent_id)->toBe($india->id)
        ->and(Location::query()->where('type', LocationType::City)->count())->toBe(3)
        ->and(Location::query()->where('type', LocationType::ItPark)->count())->toBe(3)
        ->and(Location::query()->where('name', 'Technopark')->firstOrFail()->parent->name)->toBe('Thiruvananthapuram');
});

test('countries may contain cities directly without requiring a state or province', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

    $singapore = app(SaveLocation::class)->create([
        'type' => LocationType::Country, 'name' => 'Singapore', 'parent_id' => null, 'country_code' => 'SG',
    ], $admin);
    $city = app(SaveLocation::class)->create([
        'type' => LocationType::City, 'name' => 'Singapore', 'parent_id' => $singapore->id,
    ], $admin);

    expect($city->parent->is($singapore))->toBeTrue()
        ->and($city->path())->toBe('singapore/singapore');
});

test('location actions reject writes from users without location permissions', function (): void {
    $candidate = User::factory()->create();

    expect(fn () => app(SaveLocation::class)->create([
        'type' => LocationType::Country, 'name' => 'Example', 'parent_id' => null, 'country_code' => 'EX',
    ], $candidate))->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('locations', ['country_code' => 'EX']);
});

test('public location pages resolve hierarchical slugs and expose canonical urls', function (): void {
    $this->get('/locations/india/kerala/kochi')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/locations/india/kerala/kochi').'">', false)
        ->assertSee('Explore nearby locations')
        ->assertSee('Infopark');

    $this->get('/locations/india/unknown-city')->assertNotFound();
});

test('admin CRUD is permission-protected and validates hierarchy', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $india = Location::query()->where('country_code', 'IN')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.locations.store'), [
        'type' => LocationType::StateProvince->value, 'name' => 'Karnataka', 'parent_id' => $india->id,
    ])->assertRedirect(route('admin.locations.index'));
    $this->assertDatabaseHas('locations', ['name' => 'Karnataka', 'type' => LocationType::StateProvince->value]);

    $this->from(route('admin.locations.create'))->post(route('admin.locations.store'), [
        'type' => LocationType::Area->value, 'name' => 'Invalid hierarchy', 'parent_id' => $india->id,
    ])->assertSessionHasErrors('parent_id');

    $candidate = User::factory()->create();
    $this->actingAs($candidate)->get(route('admin.locations.index'))->assertForbidden();
});

test('administrators can inspect forms, update location records, and delete unused leaves', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $city = Location::query()->where('name', 'Kochi')->firstOrFail();
    $area = app(SaveLocation::class)->create([
        'type' => LocationType::Area, 'name' => 'Kakkanad', 'parent_id' => $city->id,
    ], $admin);

    $this->actingAs($admin)->get(route('admin.locations.index'))->assertOk()->assertSee('Kakkanad');
    $this->get(route('admin.locations.create'))->assertOk()->assertSee('Country code');
    $this->get(route('admin.locations.edit', $area))->assertOk()->assertSee('Edit location');
    $this->put(route('admin.locations.update', $area), [
        'type' => LocationType::Area->value, 'name' => 'Kakkanad West', 'parent_id' => $city->id,
    ])->assertRedirect(route('admin.locations.index'));
    $this->assertDatabaseHas('locations', ['id' => $area->id, 'slug' => 'kakkanad-west']);
    $this->delete(route('admin.locations.destroy', $area))->assertRedirect(route('admin.locations.index'));
    $this->assertDatabaseMissing('locations', ['id' => $area->id]);
});

test('profile updates can store a structured primary location', function (): void {
    $user = User::factory()->create();
    $city = Location::query()->where('name', 'Kozhikode')->firstOrFail();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name, 'location_id' => $city->id, 'timezone' => 'Asia/Kolkata', 'locale' => 'en',
    ])->assertRedirect();

    expect($user->fresh()->profile->primaryLocation->is($city))->toBeTrue();
});

test('location actions prevent moving a node beneath its own descendant', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
    $india = Location::query()->where('country_code', 'IN')->firstOrFail();
    $kerala = Location::query()->where('name', 'Kerala')->firstOrFail();
    $city = Location::query()->where('name', 'Kochi')->firstOrFail();

    expect(fn () => app(SaveLocation::class)->update($kerala, [
        'type' => LocationType::StateProvince, 'name' => 'Kerala', 'parent_id' => $city->id,
    ], $admin))->toThrow(ValidationException::class);

    expect($kerala->fresh()->parent_id)->toBe($india->id);
});

test('talent profiles and future marketplace models can attach locations by context', function (): void {
    $profile = UserProfile::factory()->for(User::factory())->create();
    $city = Location::query()->where('name', 'Kochi')->firstOrFail();
    $park = Location::query()->where('name', 'Infopark')->firstOrFail();

    $profile->update(['location_id' => $city->id]);
    $profile->locations()->attach($park->id, ['context' => 'workplace']);

    expect($profile->fresh()->primaryLocation->is($city))->toBeTrue()
        ->and($profile->fresh()->locations()->wherePivot('context', 'workplace')->first()->is($park))->toBeTrue();
    $this->assertDatabaseHas('location_assignments', [
        'locationable_type' => $profile->getMorphClass(), 'locationable_id' => $profile->id,
        'location_id' => $park->id, 'context' => 'workplace',
    ]);
});
