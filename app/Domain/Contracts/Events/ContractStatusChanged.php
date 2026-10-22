<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ContractStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $contractId, public string $fromStatus, public string $toStatus) {}
}
