<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfileExperience;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileExperience> */
class ProfileExperienceFactory extends Factory
{
    protected $model = ProfileExperience::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'title' => fake()->jobTitle(), 'company' => fake()->company(), 'location' => fake()->city(), 'description' => fake()->paragraph(), 'started_at' => fake()->dateTimeBetween('-8 years', '-2 years'), 'ended_at' => null, 'is_current' => true];
    }
}
