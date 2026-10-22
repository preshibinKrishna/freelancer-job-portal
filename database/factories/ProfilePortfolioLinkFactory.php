<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfilePortfolioLink;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfilePortfolioLink> */
class ProfilePortfolioLinkFactory extends Factory
{
    protected $model = ProfilePortfolioLink::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'title' => fake()->sentence(3), 'url' => fake()->url(), 'description' => fake()->sentence()];
    }
}
