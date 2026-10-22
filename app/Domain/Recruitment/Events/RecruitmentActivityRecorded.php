<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class RecruitmentActivityRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $recruitmentRequestId, public string $activityType) {}
}
