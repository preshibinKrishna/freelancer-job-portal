<?php

declare(strict_types=1);

namespace App\Enums;

enum JobVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
}
