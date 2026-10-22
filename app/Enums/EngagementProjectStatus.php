<?php

declare(strict_types=1);

namespace App\Enums;

enum EngagementProjectStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Reviewing = 'reviewing';
    case Awarded = 'awarded';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function isDiscoverable(): bool
    {
        return in_array($this, [self::Published, self::Reviewing], true);
    }
}
