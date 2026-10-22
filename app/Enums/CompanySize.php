<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanySize: string
{
    case Solo = '1';
    case Small = '2-10';
    case Growing = '11-50';
    case MidSize = '51-200';
    case Large = '201-500';
    case Enterprise = '501-1000';
    case Global = '1001+';

    public function label(): string
    {
        return match ($this) {
            self::Solo => '1 employee',
            self::Small => '2–10 employees',
            self::Growing => '11–50 employees',
            self::MidSize => '51–200 employees',
            self::Large => '201–500 employees',
            self::Enterprise => '501–1,000 employees',
            self::Global => '1,001+ employees',
        };
    }
}
