<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfileLanguage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileLanguage> */
class ProfileLanguageFactory extends Factory
{
    protected $model = ProfileLanguage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'language' => fake()->languageName(), 'proficiency' => fake()->randomElement(['Native', 'Professional', 'Conversational', 'Basic'])];
    }
}
