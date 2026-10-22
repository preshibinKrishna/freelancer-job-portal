<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ContractMilestoneStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $milestoneId, public string $fromStatus, public string $toStatus) {}
}
