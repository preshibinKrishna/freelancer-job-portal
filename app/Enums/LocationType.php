<?php

declare(strict_types=1);

namespace App\Enums;

enum LocationType: string
{
    case Country = 'country';
    case StateProvince = 'state_province';
    case City = 'city';
    case Area = 'area';
    case ItPark = 'it_park';

    public function label(): string
    {
        return match ($this) {
            self::Country => 'Country',
            self::StateProvince => 'State / Province',
            self::City => 'City',
            self::Area => 'Area',
            self::ItPark => 'IT Park',
        };
    }

    /** @return list<LocationType> */
    public function allowedParentTypes(): array
    {
        return match ($this) {
            self::Country => [],
            self::StateProvince => [self::Country],
            self::City => [self::Country, self::StateProvince],
            self::Area => [self::City],
            self::ItPark => [self::City, self::Area],
        };
    }
}
