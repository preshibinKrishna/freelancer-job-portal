<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingOperationType: string
{
    case Charge = 'charge';
    case Refund = 'refund';
    case PlatformFee = 'platform_fee';
}
