<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractMilestoneStatus: string
{
    case Pending = 'pending';
    case Funded = 'funded';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Disputed = 'disputed';
    case Released = 'released';
}
