<?php

declare(strict_types=1);

namespace App\Enums;

enum JobApplicationStatus: string
{
    case Applied = 'applied';
    case Reviewing = 'reviewing';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /** @return list<JobApplicationStatus> */
    public function employerTransitions(): array
    {
        return match ($this) {
            self::Applied => [self::Reviewing, self::Shortlisted, self::Rejected],
            self::Reviewing => [self::Shortlisted, self::Rejected],
            self::Shortlisted => [self::Interview, self::Rejected],
            self::Interview => [self::Offer, self::Rejected],
            self::Offer => [self::Hired, self::Rejected],
            self::Hired, self::Rejected, self::Withdrawn => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected, self::Withdrawn], true);
    }
}
