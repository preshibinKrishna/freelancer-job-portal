<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanySize;
use App\Enums\CompanyVerificationStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company): void {
            CompanyMembership::query()->firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $company->owner_id],
                ['role' => CompanyMemberRole::Owner, 'status' => CompanyMembershipStatus::Active],
            );
        });
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => UserFactory::new(),
            'name' => $name,
            'slug' => Str::slug($name),
            'industry' => fake()->randomElement(['Software Development', 'Information Technology', 'Cybersecurity']),
            'company_size' => fake()->randomElement(CompanySize::cases()),
            'verification_status' => CompanyVerificationStatus::Unverified,
            'social_links' => [],
            'benefits' => [],
            'technology_stack' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published_at' => now()]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['verification_status' => CompanyVerificationStatus::Verified]);
    }
}
