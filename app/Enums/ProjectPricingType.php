<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectPricingType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed price',
            self::Hourly => 'Hourly',
        };
    }
}
