<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Temporary = 'temporary';
    case Internship = 'internship';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::Contract => 'Contract',
            self::Temporary => 'Temporary',
            self::Internship => 'Internship',
        };
    }
}
