<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FreelancerVerificationStatus;
use App\Models\FreelancerProfile;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FreelancerProfile> */
class FreelancerProfileFactory extends Factory
{
    protected $model = FreelancerProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'hourly_rate' => fake()->randomFloat(2, 25, 180), 'project_rate' => fake()->randomFloat(2, 500, 10000), 'currency' => 'USD', 'weekly_capacity' => fake()->numberBetween(5, 40), 'is_listed' => false, 'verification_status' => FreelancerVerificationStatus::Unverified];
    }

    public function listed(): static
    {
        return $this->state(['is_listed' => true]);
    }
}
