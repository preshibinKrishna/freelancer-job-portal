<?php

declare(strict_types=1);

namespace App\Enums;

enum ProfileAvailability: string
{
    case ActivelyLooking = 'actively_looking';
    case OpenToOffers = 'open_to_offers';
    case NotLooking = 'not_looking';

    public function label(): string
    {
        return match ($this) {
            self::ActivelyLooking => 'Actively looking',
            self::OpenToOffers => 'Open to offers',
            self::NotLooking => 'Not looking',
        };
    }
}
