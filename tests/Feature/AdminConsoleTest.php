<?php

declare(strict_types=1);

use App\Domain\Geography\Actions\DeleteLocation;
use App\Domain\Geography\Actions\SaveLocation;
use App\Enums\LocationType;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IdentityAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(IdentityAccessSeeder::class);
});

test('administrators with section permissions can use the admin workspace', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', 'admin')->firstOrFail();
    $admin->roles()->attach($role->id);

    $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Platform overview');
    $this->actingAs($admin)->get('/admin/jobs')->assertOk()->assertSee('Jobs');

    foreach (['candidates', 'freelancers', 'employers', 'companies', 'projects', 'applications', 'proposals', 'contracts', 'recruitment', 'verification', 'reviews', 'reports', 'payments', 'invoices', 'locations', 'it-parks', 'skills', 'categories', 'content', 'settings', 'audit-logs'] as $section) {
        $this->get('/admin/'.$section)->assertOk();
    }
});

test('admin sections reject users without the matching permission', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
    $this->actingAs($user)->get('/admin/payments')->assertForbidden();
});

test('location administration authorizes and audits create and delete operations', function (): void {
    $admin = User::factory()->create();
    $role = Role::query()->where('name', 'admin')->firstOrFail();
    $admin->roles()->attach($role->id);

    $location = app(SaveLocation::class)->create([
        'type' => LocationType::Country,
        'name' => 'Example Country',
        'parent_id' => null,
        'country_code' => 'EX',
    ], $admin);
    expect(AuditLog::query()->where('actor_id', $admin->id)->where('event', 'admin.location_created')->exists())->toBeTrue();

    app(DeleteLocation::class)->execute($location, $admin);
    expect(Location::query()->whereKey($location->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('actor_id', $admin->id)->where('event', 'admin.location_deleted')->exists())->toBeTrue();
});
