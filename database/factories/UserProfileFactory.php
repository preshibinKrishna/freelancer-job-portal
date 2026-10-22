<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserProfile> */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'headline' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'location' => fake()->city(),
        ];
    }
}
