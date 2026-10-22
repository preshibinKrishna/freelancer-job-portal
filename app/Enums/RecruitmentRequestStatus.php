<?php

declare(strict_types=1);

namespace App\Enums;

enum RecruitmentRequestStatus: string
{
    case Submitted = 'submitted';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Filled = 'filled';
    case Cancelled = 'cancelled';
}
