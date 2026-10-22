<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserIdentity> */
class UserIdentityFactory extends Factory
{
    protected $model = UserIdentity::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['google', 'linkedin', 'github']),
            'provider_subject' => fake()->unique()->uuid(),
            'email' => fake()->safeEmail(),
        ];
    }
}
