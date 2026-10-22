<?php

declare(strict_types=1);

namespace App\Enums;

enum CandidatePipelineStage: string
{
    case New = 'new';
    case Sourcing = 'sourcing';
    case Screening = 'screening';
    case Shortlisted = 'shortlisted';
    case ClientReview = 'client_review';
    case Interview = 'interview';
    case Offer = 'offer';
    case Placed = 'placed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New', self::Sourcing => 'Sourcing', self::Screening => 'Screening',
            self::Shortlisted => 'Shortlisted', self::ClientReview => 'Client Review',
            self::Interview => 'Interview', self::Offer => 'Offer', self::Placed => 'Placed', self::Rejected => 'Rejected',
        };
    }

    /** @return list<CandidatePipelineStage> */
    public function nextStages(): array
    {
        return match ($this) {
            self::New => [self::Sourcing, self::Rejected],
            self::Sourcing => [self::Screening, self::Rejected],
            self::Screening => [self::Shortlisted, self::Rejected],
            self::Shortlisted => [self::ClientReview, self::Rejected],
            self::ClientReview => [self::Interview, self::Rejected],
            self::Interview => [self::Offer, self::Rejected],
            self::Offer => [self::Placed, self::Rejected],
            self::Placed, self::Rejected => [],
        };
    }
}
