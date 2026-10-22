<?php

declare(strict_types=1);

namespace App\Enums;

enum FreelanceContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
