<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;

class GeographicLocationSeeder extends Seeder
{
    public function run(): void
    {
        $india = Location::query()->firstOrCreate(
            ['country_code' => 'IN'],
            ['type' => LocationType::Country, 'name' => 'India', 'slug' => 'india'],
        );
        $kerala = $this->location(LocationType::StateProvince, 'Kerala', $india);

        $trivandrum = $this->location(LocationType::City, 'Thiruvananthapuram', $kerala);
        $kochi = $this->location(LocationType::City, 'Kochi', $kerala);
        $kozhikode = $this->location(LocationType::City, 'Kozhikode', $kerala);

        $this->location(LocationType::ItPark, 'Technopark', $trivandrum);
        $this->location(LocationType::ItPark, 'Infopark', $kochi);
        $this->location(LocationType::ItPark, 'Cyberpark', $kozhikode);
    }

    private function location(LocationType $type, string $name, Location $parent): Location
    {
        return Location::query()->firstOrCreate(
            ['parent_id' => $parent->id, 'type' => $type, 'slug' => str($name)->slug()->toString()],
            ['name' => $name],
        );
    }
}
