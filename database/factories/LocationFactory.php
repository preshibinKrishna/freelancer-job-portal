<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->country();

        return [
            'type' => LocationType::Country,
            'name' => $name,
            'slug' => Str::slug($name),
            'country_code' => strtoupper(fake()->unique()->lexify('??')),
        ];
    }

    public function ofType(LocationType $type): static
    {
        return $this->state(fn (): array => ['type' => $type, 'country_code' => null]);
    }
}
