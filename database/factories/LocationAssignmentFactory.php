<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LocationAssignment;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LocationAssignment> */
class LocationAssignmentFactory extends Factory
{
    protected $model = LocationAssignment::class;

    public function definition(): array
    {
        return [
            'location_id' => LocationFactory::new(),
            'locationable_type' => (new UserProfile)->getMorphClass(),
            'locationable_id' => UserProfile::factory(),
            'context' => 'primary',
        ];
    }
}
