<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfileEducation;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileEducation> */
class ProfileEducationFactory extends Factory
{
    protected $model = ProfileEducation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'institution' => fake()->company().' University', 'degree' => "Bachelor's degree", 'field_of_study' => fake()->jobTitle(), 'started_at' => fake()->dateTimeBetween('-10 years', '-5 years'), 'ended_at' => fake()->dateTimeBetween('-5 years', '-1 year')];
    }
}
