<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectProposalStatus: string
{
    case Submitted = 'submitted';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Awarded = 'awarded';
    case Withdrawn = 'withdrawn';
}
