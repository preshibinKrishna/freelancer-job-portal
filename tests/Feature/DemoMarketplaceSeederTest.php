<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DemoMarketplaceSeeder;
use Database\Seeders\GeographicLocationSeeder;
use Database\Seeders\IdentityAccessSeeder;
use Database\Seeders\ProjectCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo marketplace seeder creates browseable sample content and is idempotent', function (): void {
    $this->seed([
        IdentityAccessSeeder::class,
        GeographicLocationSeeder::class,
        CompanySeeder::class,
        ProjectCategorySeeder::class,
    ]);

    User::factory()->create(['email' => 'employer@example.test']);
    User::factory()->create(['email' => 'admin@example.test']);

    $this->seed(DemoMarketplaceSeeder::class);

    expect(Company::query()->whereNotNull('published_at')->count())->toBe(5)
        ->and(Job::query()->discoverable()->count())->toBe(6)
        ->and(FreelancerProfile::query()->discoverable()->count())->toBe(4)
        ->and(EngagementProject::query()->discoverable()->count())->toBe(4)
        ->and(Role::query()->where('name', 'freelancer')->exists())->toBeTrue();

    $this->seed(DemoMarketplaceSeeder::class);

    expect(Company::query()->whereNotNull('published_at')->count())->toBe(5)
        ->and(Job::query()->discoverable()->count())->toBe(6)
        ->and(FreelancerProfile::query()->discoverable()->count())->toBe(4)
        ->and(EngagementProject::query()->discoverable()->count())->toBe(4);
});
