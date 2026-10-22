<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfileCertification;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileCertification> */
class ProfileCertificationFactory extends Factory
{
    protected $model = ProfileCertification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'name' => fake()->jobTitle().' Certification', 'issuer' => fake()->company(), 'credential_url' => fake()->url(), 'issued_at' => fake()->dateTimeBetween('-4 years', '-1 year'), 'expires_at' => null];
    }
}
